<?php

namespace App\Services\Attendance\Connectors;

use App\Models\AttendanceDevice;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HikvisionConnector extends BaseAttendanceConnector
{
    /**
     * Test connection to a Hikvision device.
     */
    public function testConnection(
        AttendanceDevice $device
    ): bool {
        $this->validateDeviceConfiguration($device);

        $password = $this->decryptPassword($device);

        $url = $this->buildUrl(
            $device,
            '/ISAPI/System/deviceInfo'
        );

        try {
            $response = Http::withDigestAuth(
                $device->username,
                $password
            )
                ->timeout(10)
                ->acceptJson()
                ->get($url);

            if ($response->successful()) {
                return true;
            }

            throw new RuntimeException(
                sprintf(
                    'Hikvision device returned HTTP %s: %s',
                    $response->status(),
                    $response->body()
                )
            );
        } catch (\Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException(
                'Unable to connect to Hikvision device: '
                . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Fetch punches from Hikvision.
     *
     * Not implemented yet.
     */
   public function fetchPunches(
    AttendanceDevice $device,
    ?\DateTimeInterface $from = null,
    ?\DateTimeInterface $to = null
): iterable {
    $this->validateDeviceConfiguration($device);

    $password = $this->decryptPassword($device);

    $url = $this->buildUrl(
        $device,
        '/ISAPI/AccessControl/AcsEvent?format=json'
    );

    $timezone = new \DateTimeZone(
        $device->timezone ?: config('app.timezone')
    );

    $from = $from
        ? \Carbon\Carbon::instance(
            \DateTime::createFromInterface($from)
        )->setTimezone($timezone)
        : now($timezone)->subDay();

    $to = $to
        ? \Carbon\Carbon::instance(
            \DateTime::createFromInterface($to)
        )->setTimezone($timezone)
        : now($timezone);

    $position = 0;
    $searchResultPosition = 0;
    $maxResults = 100;

    $searchId = 'payrow-' . $device->id . '-' . uniqid();

    while (true) {
        $requestBody = [
            'AcsEventCond' => [
                'searchID' => $searchId,
                'searchResultPosition' => $searchResultPosition,
                'maxResults' => $maxResults,
                'major' => 0,
                'minor' => 0,
                'startTime' => $from->format('Y-m-d\TH:i:sP'),
                'endTime' => $to->format('Y-m-d\TH:i:sP'),
            ],
        ];

        $response = Http::withDigestAuth(
            $device->username,
            $password
        )
            ->timeout(30)
            ->acceptJson()
            ->post($url, $requestBody);

        if (!$response->successful()) {
            throw new RuntimeException(
                sprintf(
                    'Hikvision event request failed with HTTP %s: %s',
                    $response->status(),
                    $response->body()
                )
            );
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException(
                'Hikvision returned an invalid JSON response.'
            );
        }

        $events = $this->extractEvents($payload);

        if (empty($events)) {
            break;
        }

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            yield $event;

            $position++;
        }

        if (count($events) < $maxResults) {
            break;
        }

        $searchResultPosition += count($events);
    }
}

    /**
     * Normalize a Hikvision punch.
     *
     * Not implemented yet.
     */
   public function normalizePunch(
    AttendanceDevice $device,
    array $rawPunch
): array {
    /*
     * Hikvision can return different employee identifier
     * fields depending on device family / firmware.
     */
    $deviceUserId =
        $rawPunch['employeeNoString']
        ?? $rawPunch['employeeNo']
        ?? $rawPunch['cardNo']
        ?? $rawPunch['personId']
        ?? null;

    if ($deviceUserId !== null) {
        $deviceUserId = trim((string) $deviceUserId);
    }

    if (!$deviceUserId) {
        throw new RuntimeException(
            'Hikvision event does not contain an employee/device user ID.'
        );
    }

    /*
     * Hikvision normally returns the event time in `time`,
     * but some integrations/devices may expose another field.
     */
    $punchTime =
        $rawPunch['time']
        ?? $rawPunch['dateTime']
        ?? $rawPunch['eventTime']
        ?? $rawPunch['timestamp']
        ?? null;

    if (!$punchTime) {
        throw new RuntimeException(
            'Hikvision event does not contain a timestamp.'
        );
    }

    try {
        $timezone = $device->timezone
            ?: config('app.timezone');

        $punchedAt = \Carbon\Carbon::parse(
            $punchTime,
            $timezone
        );
    } catch (\Throwable $e) {
        throw new RuntimeException(
            'Unable to parse Hikvision event timestamp.',
            previous: $e
        );
    }

    /*
     * Hikvision event identifiers vary by device.
     *
     * Prefer the vendor's serial/event ID where available.
     * Otherwise create a deterministic identifier from the
     * device + employee + timestamp + event information.
     */
    $externalId =
        $rawPunch['serialNo']
        ?? $rawPunch['eventId']
        ?? $rawPunch['id']
        ?? null;

    if ($externalId !== null) {
        $externalId = trim((string) $externalId);
    }

    if (!$externalId) {
        $externalId = sha1(
            $device->id
            . '|'
            . $deviceUserId
            . '|'
            . $punchedAt->format('Y-m-d H:i:s')
            . '|'
            . ($rawPunch['major'] ?? '')
            . '|'
            . ($rawPunch['minor'] ?? '')
            . '|'
            . ($rawPunch['eventType'] ?? '')
        );
    }

    /*
     * Do not guess IN/OUT from Hikvision event codes yet.
     *
     * Different Hikvision products can expose different
     * event-code combinations. We preserve the vendor event
     * information in raw_data and safely use "unknown" until
     * the event mapping is established.
     */
    $punchType = 'unknown';

    return [
        'device_user_id' => $deviceUserId,
        'punched_at' => $punchedAt->format('Y-m-d H:i:s'),
        'punch_type' => $punchType,
        'source' => 'device',
        'external_id' => $externalId,
        'raw_data' => $rawPunch,
    ];
}

    /**
     * Validate the minimum configuration required
     * to connect to the device.
     */
    protected function validateDeviceConfiguration(
        AttendanceDevice $device
    ): void {
        if (empty($device->ip_address) && empty($device->api_url)) {
            throw new RuntimeException(
                'Hikvision device requires an IP address or API URL.'
            );
        }

        if (empty($device->username)) {
            throw new RuntimeException(
                'Hikvision device username is required.'
            );
        }

        if (empty($device->password)) {
            throw new RuntimeException(
                'Hikvision device password is required.'
            );
        }
    }

    /**
     * Decrypt the stored device password.
     */
    protected function decryptPassword(
        AttendanceDevice $device
    ): string {
        try {
            return Crypt::decryptString(
                $device->password
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Unable to decrypt the Hikvision device password.',
                previous: $e
            );
        }
    }

    /**
     * Build the device API URL.
     */
    protected function buildUrl(
        AttendanceDevice $device,
        string $path
    ): string {
        $baseUrl = $device->api_url;

        if (!$baseUrl) {
            $host = $device->ip_address;
            $port = $device->port ?: 80;

            $baseUrl = "http://{$host}:{$port}";
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

        /**
     * Extract event records from different Hikvision response structures.
     */
   protected function extractEvents(array $payload): array
{
    $eventData = $payload['AcsEvent'] ?? $payload;

    if (!is_array($eventData)) {
        return [];
    }

    // Hikvision may return:
    // InfoList: [...]
    if (isset($eventData['InfoList'])) {
        $infoList = $eventData['InfoList'];

        // Some firmware versions wrap events inside Info.
        if (
            is_array($infoList)
            && isset($infoList['Info'])
            && is_array($infoList['Info'])
        ) {
            return array_values($infoList['Info']);
        }

        if (is_array($infoList)) {
            return array_values($infoList);
        }
    }

    // Alternative event structure.
    if (
        isset($eventData['Event'])
        && is_array($eventData['Event'])
    ) {
        return array_values($eventData['Event']);
    }

    // Some responses may expose InfoList at the root.
    if (isset($payload['InfoList'])) {
        $infoList = $payload['InfoList'];

        if (
            is_array($infoList)
            && isset($infoList['Info'])
            && is_array($infoList['Info'])
        ) {
            return array_values($infoList['Info']);
        }

        if (is_array($infoList)) {
            return array_values($infoList);
        }
    }

    return [];
}
}