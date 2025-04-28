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
