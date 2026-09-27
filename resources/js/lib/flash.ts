export type FlashTone = 'success' | 'info' | 'warning';

export interface FlashMessage {
    tone: FlashTone;
    message: string;
}

/**
 * Human text for the `status` keys controllers flash via
 * `->with('status', '...')`. Keys ending in "-" match by prefix
 * (e.g. "module-created-crm"). Unknown keys that already read as a
 * sentence (the password broker's translated messages) are shown as-is.
 */
const messages: Record<string, FlashMessage> = {
    'plan-created': { tone: 'success', message: 'Plan creado correctamente.' },
    'plan-updated': { tone: 'success', message: 'Cambios del plan guardados.' },
    'plan-activated': { tone: 'success', message: 'Plan activado.' },
    'plan-deactivated': { tone: 'warning', message: 'Plan desactivado.' },
    'module-created-': {
        tone: 'success',
        message: 'Módulo creado correctamente.',
    },
    'module-updated': {
        tone: 'success',
        message: 'Cambios del módulo guardados.',
    },
    'module-activated': { tone: 'success', message: 'Módulo activado.' },
    'module-deactivated': { tone: 'warning', message: 'Módulo desactivado.' },
    'feature-created': {
        tone: 'success',
        message: 'Feature creada correctamente.',
    },
    'feature-updated': {
        tone: 'success',
        message: 'Cambios de la feature guardados.',
    },
    'feature-activated': { tone: 'success', message: 'Feature activada.' },
    'feature-deactivated': { tone: 'warning', message: 'Feature desactivada.' },
    'limit-type-created': {
        tone: 'success',
        message: 'Tipo de límite creado.',
    },
    'limit-type-updated': {
        tone: 'success',
        message: 'Tipo de límite actualizado.',
    },
    'limit-type-activated': {
        tone: 'success',
        message: 'Tipo de límite activado.',
    },
    'limit-type-deactivated': {
        tone: 'warning',
        message: 'Tipo de límite desactivado.',
    },
    'tenant-created': {
        tone: 'success',
        message: 'Tenant aprovisionado correctamente.',
    },
    'tenant-activated': { tone: 'success', message: 'Tenant reactivado.' },
    'tenant-suspended': { tone: 'warning', message: 'Tenant suspendido.' },
    'subscription-renewed': {
        tone: 'success',
        message: 'Suscripción renovada. El espacio del tenant está habilitado.',
    },
    'role-created': { tone: 'success', message: 'Rol creado correctamente.' },
    'role-updated': { tone: 'success', message: 'Cambios del rol guardados.' },
    'role-deleted': { tone: 'success', message: 'Rol eliminado.' },
    'staff-created': { tone: 'success', message: 'Miembro del staff creado.' },
    'staff-updated': { tone: 'success', message: 'Staff actualizado.' },
    'user-updated': { tone: 'success', message: 'Usuario actualizado.' },
    'profile-updated': { tone: 'success', message: 'Perfil actualizado.' },
    'password-updated': { tone: 'success', message: 'Contraseña actualizada.' },
    'verification-link-sent': {
        tone: 'info',
        message: 'Te enviamos un nuevo enlace de verificación.',
    },
    verified: { tone: 'success', message: 'Correo verificado.' },
    'support-ticket-created': {
        tone: 'success',
        message: 'Solicitud registrada. Te avisaremos por correo.',
    },
    'support-ticket-updated': {
        tone: 'success',
        message: 'Ticket actualizado.',
    },
    'support-ticket-assigned': {
        tone: 'success',
        message: 'Responsable asignado.',
    },
    'support-reply-sent': { tone: 'success', message: 'Respuesta enviada.' },
    'support-note-added': { tone: 'info', message: 'Nota interna guardada.' },
    'support-billing-updated': {
        tone: 'success',
        message: 'Facturación del ticket actualizada.',
    },
    'support-time-logged': { tone: 'success', message: 'Tiempo registrado.' },
    'support-rated': {
        tone: 'success',
        message: '¡Gracias por tu calificación!',
    },
    'support-appointment-booked': {
        tone: 'success',
        message: 'Sesión agendada. Te enviamos la confirmación.',
    },
    'support-appointment-updated': {
        tone: 'success',
        message: 'Sesión actualizada.',
    },
    'support-appointment-cancelled': {
        tone: 'warning',
        message: 'Sesión cancelada.',
    },
    'support-settings-updated': {
        tone: 'success',
        message: 'Configuración de soporte guardada.',
    },
    'support-blackout-applied': {
        tone: 'warning',
        message:
            'Bloqueo registrado: se reasignaron o cancelaron citas afectadas.',
    },
    'support-invoiced': {
        tone: 'success',
        message: 'Tickets marcados como facturados.',
    },
};

export function resolveFlash(
    status: string | null | undefined,
): FlashMessage | null {
    if (!status) {
        return null;
    }

    if (messages[status]) {
        return messages[status];
    }

    const prefixed = Object.keys(messages).find(
        (key) => key.endsWith('-') && status.startsWith(key),
    );

    if (prefixed) {
        return messages[prefixed];
    }

    return status.includes(' ') ? { tone: 'info', message: status } : null;
}
