/**
 * Shared class strings for form controls and buttons, so new screens stay
 * consistent with the design tokens in resources/css/app.css.
 */
export const ui = {
    label: 'form-label',
    input: 'w-full form-control',
    checkbox: 'form-check',
    error: 'form-error',
    help: 'form-hint',
    buttonPrimary:
        'inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50',
    buttonSecondary:
        'inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-ink-200 bg-white px-4 text-sm font-semibold text-ink-700 shadow-sm transition hover:bg-ink-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-ink-700 dark:bg-ink-900 dark:text-ink-200 dark:hover:bg-ink-800',
    buttonDanger:
        'inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-500/10',
    link: 'font-semibold text-primary-700 hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-200',
};
