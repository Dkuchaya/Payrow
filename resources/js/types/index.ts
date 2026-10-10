export interface Company {
    id: number
    name: string
    legal_name?: string | null
    logo?: string | null
    currency?: string
}

export interface CompanyProps {
    active: Company | null
    available: Company[]
}