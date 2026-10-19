const clp = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });

export const money = (value) => clp.format(value ?? 0);

const TZ = 'America/Santiago';

export const dateTime = (iso) =>
    iso ? new Intl.DateTimeFormat('es-CL', { dateStyle: 'medium', timeStyle: 'short', timeZone: TZ }).format(new Date(iso)) : '—';

export const date = (iso) =>
    iso ? new Intl.DateTimeFormat('es-CL', { dateStyle: 'full', timeZone: TZ }).format(new Date(iso)) : '—';

export const time = (iso) =>
    iso ? new Intl.DateTimeFormat('es-CL', { hour: '2-digit', minute: '2-digit', timeZone: TZ }).format(new Date(iso)) : '';

/** Fecha local YYYY-MM-DD (para enviar al backend). */
export const ymd = (d) => {
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

export const orderStatusType = (status) =>
    ({ paid: 'success', pending: 'warning', failed: 'danger', expired: 'info', cancelled: 'info' })[status] ?? 'info';

export const appointmentStatusType = (status) =>
    ({ pending: 'warning', confirmed: 'primary', completed: 'success', cancelled: 'info' })[status] ?? 'info';

/** Enlace a WhatsApp con mensaje prellenado, o null si no está configurado. */
export const whatsappUrl = (contact, text = null) =>
    contact?.whatsapp
        ? `https://wa.me/${String(contact.whatsapp).replace(/\D/g, '')}?text=${encodeURIComponent(text ?? contact.whatsapp_message ?? '')}`
        : null;

export const firstError = (error) => {
    const errors = error?.response?.data?.errors;
    if (errors) return Object.values(errors).flat()[0];
    return error?.response?.data?.message || 'Ocurrió un error inesperado.';
};
