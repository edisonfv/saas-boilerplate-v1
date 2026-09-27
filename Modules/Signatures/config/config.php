<?php

return [
    'name' => 'Signatures',

    /*
    | Catalog slug of the sellable module (App\Models\Module::$slug).
    */
    'module_slug' => 'signatures',

    /*
    | Return the consumed unit (prepaid) or credit (credit) to the tenant when
    | the provider rejects or cancels a request, since no certificate was
    | issued. Set to false if the provider bills rejected requests anyway.
    */
    'refund_unfulfilled' => (bool) env('SIGNATURES_REFUND_UNFULFILLED', true),

    /*
    | Private disk (tenant-suffixed by FilesystemTenancyBootstrapper) where
    | applicants' KYC documents are stored.
    */
    'documents_disk' => 'local',

    /*
    | Disk (tenant-suffixed by FilesystemTenancyBootstrapper) for the public
    | website's banner photos, served with stancl's tenant_asset() route.
    */
    'media_disk' => 'public',

    /*
    | Max upload size per document, in kilobytes (Uanataca accepts 13 MB for
    | identity images and up to 35 MB for company deeds).
    */
    'max_document_kilobytes' => 13312,
];
