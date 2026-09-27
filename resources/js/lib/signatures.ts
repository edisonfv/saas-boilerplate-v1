type BadgeTone = 'green' | 'amber' | 'red' | 'gray' | 'blue';

export interface SignatureProduct {
    id: string;
    name: string;
    validity: string;
    validity_label: string;
    container: string;
    container_label: string;
    credit_unit_price: string;
    suggested_retail_price: string | null;
    currency: string;
    is_active: boolean;
    retail_price?: string | null;
}

export interface SignatureAccountProduct extends SignatureProduct {
    available_units: number;
    sellable_units: number;
}

/** Shape of App\Services\Signatures\SignaturePresenter::account(). */
export interface SignatureAccount {
    id: string;
    affiliation_mode: 'Credit' | 'Prepaid';
    affiliation_mode_label: string;
    is_credit: boolean;
    is_active: boolean;
    credit_limit: string;
    credit_used: string;
    credit_available: string;
    notes: string | null;
    products: SignatureAccountProduct[];
}

export interface DocumentKindOption {
    kind: string;
    label: string;
    accept: string;
}

/** Option lists from SignaturePresenter::formOptions(). */
export interface SignatureFormOptions {
    products: SignatureProduct[];
    applicantTypes: Record<string, string>;
    documentTypes: Record<string, string>;
    genders: Record<string, string>;
    documentKinds: DocumentKindOption[];
    requiredDocuments: Record<string, string[]>;
}

export interface SignatureRequestSummary {
    id: string;
    code: string;
    applicant_name: string;
    document_number: string;
    company_name: string | null;
    product_name: string;
    source: string;
    source_label: string;
    status: string;
    status_label: string;
    sale_price: string | null;
    created_at: string;
}

/** Public storefront props (StorefrontController::storefrontProps()). */
export interface StorefrontInfo {
    company_name: string;
    headline: string;
    description: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    /** wa.me link with the tenant's pre-filled message (null if not set). */
    whatsapp_url: string | null;
}

export function money(value: string | number | null | undefined): string {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('es-EC', {
        style: 'currency',
        currency: 'USD',
    }).format(Number.isFinite(amount) ? amount : 0);
}

/**
 * Badge tones for App\Enums\SignatureRequestStatus values. Kept in sync by hand.
 */
export function signatureStatusTone(status: string): BadgeTone {
    switch (status) {
        case 'Draft':
            return 'gray';
        case 'Submitted':
        case 'InValidation':
        case 'Approved':
            return 'blue';
        case 'UpdateRequested':
        case 'Suspended':
            return 'amber';
        case 'Issued':
            return 'green';
        case 'Rejected':
        case 'Cancelled':
        case 'Revoked':
            return 'red';
        default:
            return 'gray';
    }
}

/**
 * Badge tones for App\Enums\SignatureLedgerEntryType values.
 */
export function ledgerEntryTone(type: string): BadgeTone {
    switch (type) {
        case 'PackagePurchase':
        case 'Payment':
            return 'green';
        case 'Consumption':
            return 'blue';
        case 'Refund':
            return 'amber';
        default:
            return 'gray';
    }
}
