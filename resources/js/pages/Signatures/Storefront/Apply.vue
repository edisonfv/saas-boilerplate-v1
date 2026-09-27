<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import SignatureApplicationForm from '@/components/signatures/SignatureApplicationForm.vue';
import StorefrontShell from '@/components/signatures/StorefrontShell.vue';
import type { SignatureFormOptions, StorefrontInfo } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import { home } from '@/routes';
import tenant from '@/routes/tenant';

const props = defineProps<
    SignatureFormOptions & {
        storefront: StorefrontInfo;
        selectedProductId: string | null;
        /** Present when opened from a prepaid single-use link. */
        invitation?: {
            customer_name: string;
            product_name: string;
            is_usable: boolean;
            action: string;
        } | null;
    }
>();

const page = usePage();
const invitationError = computed(
    () => (page.props.errors as Record<string, string>).invitation,
);

const options = computed<SignatureFormOptions>(() => ({
    products: props.products,
    applicantTypes: props.applicantTypes,
    applicantTypeCards: props.applicantTypeCards,
    documentTypes: props.documentTypes,
    genders: props.genders,
    documentKinds: props.documentKinds,
    requiredDocuments: props.requiredDocuments,
}));
</script>

<template>
    <Head :title="`Solicita tu firma · ${storefront.company_name}`" />

    <StorefrontShell :storefront="storefront" :show-navigation="false">
        <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6">
            <div class="app-grid">
                <div class="col-span-12 lg:col-span-8 lg:col-start-3">
                    <p class="eyebrow text-primary-600 dark:text-primary-400">
                        Solicitud en línea
                    </p>
                    <h1
                        class="mt-2 text-3xl font-extrabold text-ink-950 dark:text-white"
                    >
                        {{
                            invitation
                                ? `Hola, ${invitation.customer_name}`
                                : 'Empecemos'
                        }}
                    </h1>
                    <p class="mt-1 text-ink-600 dark:text-ink-400">
                        Completa tus datos tal como constan en tu cédula. Te
                        tomará unos minutos.
                    </p>

                    <div
                        v-if="invitation && invitation.is_usable"
                        class="mt-4 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200"
                    >
                        <Icon name="check-circle" class="size-5 shrink-0" />
                        <p>
                            Tu <strong>{{ invitation.product_name }}</strong> ya
                            está pagada. Este enlace es personal y de un solo
                            uso.
                        </p>
                    </div>
                    <p
                        v-if="invitationError"
                        :class="[ui.error, 'mt-4 text-sm']"
                    >
                        {{ invitationError }}
                    </p>
                </div>

                <div
                    v-if="invitation && !invitation.is_usable"
                    class="col-span-12 rounded-xl border border-amber-200 bg-amber-50 p-6 text-center text-amber-800 lg:col-span-8 lg:col-start-3 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                >
                    <p class="font-bold">Este enlace ya fue usado o venció.</p>
                    <p class="mt-1 text-sm">
                        Si necesitas uno nuevo, escríbenos.
                    </p>
                    <a
                        v-if="storefront.whatsapp_url"
                        :href="storefront.whatsapp_url"
                        target="_blank"
                        rel="noopener"
                        :class="[ui.buttonPrimary, 'mt-4']"
                        >Escríbenos por WhatsApp</a
                    >
                </div>

                <SignatureApplicationForm
                    v-else
                    :options="options"
                    :action="
                        invitation
                            ? invitation.action
                            : tenant.signatures.storefront.store().url
                    "
                    :selected-product-id="selectedProductId"
                    :whatsapp-url="storefront.whatsapp_url"
                    is-public
                    wizard
                    submit-label="Enviar solicitud"
                    :cancel-href="home().url"
                />
            </div>
        </div>
    </StorefrontShell>
</template>
