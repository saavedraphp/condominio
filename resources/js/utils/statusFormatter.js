/**
 * Transforma un estado de API en un objeto con texto, color e icono para la UI.
 * @param {string} status - El estado recibido de la API (ej. 'SUCCESS').
 * @returns {{text: string, color: string, icon: string}}
 */
export function formatLogStatus(status) {
    if (!status) {
        return { text: 'Desconocido', color: 'grey', icon: 'mdi-help-circle-outline' };
    }

    const lowerCaseStatus = status.toLowerCase();

    switch (lowerCaseStatus) {
        case 'success':
            return {
                text: 'Acceso Permitido',
                color: 'success',
                icon: 'mdi-check-circle'
            };
        case 'failed_expired':
            return {
                text: 'Pase Caducado',
                color: 'warning',
                icon: 'mdi-clock-alert-outline'
            };
        case 'failed_not_found':
            return {
                text: 'Pase Inválido',
                color: 'error',
                icon: 'mdi-close-circle'
            };
        default:
            return {
                text: status, // Muestra el estado original si no lo conocemos
                color: 'grey',
                icon: 'mdi-alert-circle-outline'
            };
    }
}
