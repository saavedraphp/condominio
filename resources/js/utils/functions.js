import dayjs from 'dayjs';
// Opcional: para formatos en español (ej. "agosto" en lugar de "August")
import 'dayjs/locale/es';

dayjs.locale('es'); // Configura el idioma globalmente

/**
 * Muestra una alerta simple.
 * @param {string} message El mensaje a mostrar.
 */
export function showAlert(message) {
    alert(message);
}

/**
 * Registra datos en la consola con un prefijo.
 * @param {*} data Los datos a registrar.
 * @param {string} prefix Un prefijo opcional para el log.
 */
export function logData(data, prefix = 'DEBUG:') {
    console.log(prefix, data);
}

// Puedes exportar tantas funciones como necesites
export function sum(a, b) {
    return a + b;
}

export function formatDate(dateString) {
    if (!dateString) return '-';

    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    };

    //CUANDO USAS NEW DATE => CONVIERTE LA HORA A LA ZONA HORARIA
    //Cuando JavaScript ve new Date('2025-01-29') LO INTEPRETA COMO (UTC). Es decir, 2025-01-29T00:00:00.000Z.
    //Luego, el navegador convierte esa hora UTC a la zona horaria local del usuario.
    //GMT-5 (Colombia/Perú), esa hora se convierte a las 7 PM del día anterior (2025-01-28).

    const date = new Date(dateString);
    // --- A partir de aquí hacemos el cambio ---

    // 1. Obtenemos las partes de la fecha del objeto 'date'
    const year = date.getFullYear(); // Obtiene el año (ej: 2025)

    // getMonth() devuelve 0-11 (Enero=0). Sumamos 1 para tener 1-12.
    // padStart(2, '0') asegura que tenga dos dígitos (ej: '01' en vez de '1')
    const month = String(date.getMonth() + 1).padStart(2, '0');

    // getDate() devuelve el día del mes. También le aplicamos padStart.
    const day = String(date.getDate()).padStart(2, '0');

    // 2. Unimos las partes en el formato deseado con barras
    return `${year}/${month}/${day}`;
}

export function formatDateCustom(dateString, formatString = 'YYYY-MM-DD HH:mm') {
    if (!dateString || !formatString) return '-';

    const date = dayjs(dateString);
    if (!date.isValid()) {
        console.error("Fecha inválida:", dateString);
        return '-';
    }

    return date.format(formatString);
}

export function formatDateTime(dateString) {
    if (!dateString) return '-';
    const options = {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'};
    return new Date(dateString).toLocaleDateString(undefined, options);
}

export function formatDateForDisplay(dateString) {
    if (!dateString) return '-';
    const options = {year: 'numeric', month: 'short', day: 'numeric'};
    return new Date(dateString).toLocaleDateString(undefined, options);
}

export function getMegabytes(bytes) {
    if (bytes === undefined || bytes === null || typeof bytes !== 'number' || bytes < 0) {
        return '0.00';
    }

    if (bytes === 0) {
        return '0.00';
    }

    const megabytes = bytes / 1024 / 1024;

    return megabytes.toFixed(2);
}

const units = {
    electricity: 1,
    water: 0,
};

export function getUnitConsumption(typeConsumption) {
    if (typeConsumption === units.electricity) {
        return 'kWh';
    } else if (typeConsumption === units.water) {
        return 'm³';
    }

    return typeConsumption;
}

export function getDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0'); // Los meses son 0-indexados (0 para Enero)
    const day = String(today.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function getStructureTypes(type) {
    return {
        'owners_board': 'JP Isla cerdeña',
        'association_only': 'Asociación I.S.P',
        'owners_board_with_association': 'Junta y Asociación'
    };

}

export function formattedMoney(value) {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(value);
}

export function formatDateSplit(dateString) { // ej: dateString = "2025-08-19 23:59:59"
    if (!dateString) return '-';

    // 1. Tomamos solo la parte de la fecha del string (antes del espacio)
    const datePart = dateString.split(' ')[0]; // Esto nos da "2025-08-19"

    // 2. Separamos el año, mes y día
    const [year, month, day] = datePart.split('-');

    // 3. Reconstruimos en el formato que queremos (DD/MM/YYYY)
    return `${year}/${month}/${day}`;
}
