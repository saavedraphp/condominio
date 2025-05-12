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

export function formatDate  (dateString)  {
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', options);
}

export function formatDateTime(dateString)  {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
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
