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
    /** Floor central set for distributors' retail price (null = none). */
    min_retail_price: string | null;
    currency: string;
    is_active: boolean;
    retail_price?: string | null;
}

/** SignaturePresenter::centralProduct(): only ever sent to the console. */
export interface CentralSignatureProduct extends SignatureProduct {
    provider_cost: string | null;
    credit_unit_margin: string | null;
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
    accepts_images: boolean;
    accepts_pdf: boolean;
    /** Camera to open on phones: "user" (front) or "environment" (rear). */
    capture: 'user' | 'environment' | null;
}

/** Longest side, in px, photos are scaled down to before upload. */
const MaxPhotoSide = 2000;

/**
 * Normalizes a phone photo before upload: decodes it (including iPhone
 * HEIC where the browser can), scales it down and re-encodes it as JPEG.
 * Keeps uploads small on mobile data and always in a format the server
 * accepts. Returns the original file when it can't be decoded but is
 * already JPEG/PNG; throws when it's neither.
 */
export async function preparePhoto(file: File): Promise<File> {
    let bitmap: ImageBitmap;

    try {
        bitmap = await createImageBitmap(file);
    } catch {
        if (['image/jpeg', 'image/png'].includes(file.type)) {
            return file;
        }

        throw new Error(
            'No pudimos leer esta imagen. Toma la foto de nuevo o usa un archivo JPG o PNG.',
        );
    }

    const scale = Math.min(
        1,
        MaxPhotoSide / Math.max(bitmap.width, bitmap.height),
    );
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas
        .getContext('2d')
        ?.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    const blob = await new Promise<Blob | null>((resolve) =>
        canvas.toBlob(resolve, 'image/jpeg', 0.85),
    );

    if (!blob) {
        return file;
    }

    const name = file.name.replace(/\.[^.]+$/, '') || 'foto';

    return new File([blob], `${name}.jpg`, { type: 'image/jpeg' });
}

/** Option lists from SignaturePresenter::formOptions(). */
export interface SignatureFormOptions {
    products: SignatureProduct[];
    applicantTypes: Record<string, string>;
    applicantTypeCards: {
        value: string;
        label: string;
        description: string;
        /** Whether this type enables electronic invoicing (SRI). */
        invoicing: boolean;
    }[];
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
    payment_status: string;
    payment_status_label: string;
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
 * Badge tones for App\Enums\SignaturePaymentStatus values.
 */
export function paymentStatusTone(status: string): BadgeTone {
    switch (status) {
        case 'Paid':
            return 'green';
        case 'UnderReview':
            return 'blue';
        default:
            return 'amber';
    }
}

/**
 * Badge tones for App\Enums\SignaturePaymentReview values.
 */
export function paymentReviewTone(review: string): BadgeTone {
    switch (review) {
        case 'Approved':
            return 'green';
        case 'Rejected':
            return 'red';
        default:
            return 'blue';
    }
}

/** A recorded payment of a request (workspace). */
export interface PaymentRecord {
    id: string;
    method: string;
    method_label: string;
    amount: string;
    reference: string | null;
    review: string;
    review_label: string;
    rejection_reason: string | null;
    reported_by_name: string | null;
    reviewed_by_name: string | null;
    reviewed_at: string | null;
    created_at: string;
    receipt_url: string | null;
}

export interface BankAccount {
    bank: string;
    account_type: string;
    number: string;
    holder: string;
    holder_id: string;
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
