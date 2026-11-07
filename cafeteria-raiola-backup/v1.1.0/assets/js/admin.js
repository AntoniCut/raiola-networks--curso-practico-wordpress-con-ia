/*
    *  -------------------------------------------------------------------------------  *
    *  -----  admin.js  --  /plugins/cafeteria-raiola-backup/assets/js/admin.js  -----  *
    *  -------------------------------------------------------------------------------  *
*/

/*
    *  ---------------------------------  *
    *  -----  Referencias al HTML  -----  *
    *  ---------------------------------  *
*/

/* `CRB_JS` lo inyecta WordPress con wp_localize_script. */
/** @type {{ ajaxUrl: string, nonce: string, statusRunning: boolean, downloadBase: string, packageBase: string }} - `Datos del servidor` */
const CRB_JS = window.CRB_JS;

/** @type {Array<{tipo: string, extension: string}>} - `Archivos que forman un backup` */
const ARCHIVOS_BACKUP = [
    { tipo: "db", extension: "sql.gz" },
    { tipo: "files", extension: "tar.gz" },
    { tipo: "manifest", extension: "json" },
];

/** @type {HTMLButtonElement} - `Botón crear backup` */
const botonCrear = /** @type {HTMLButtonElement} */ (
    document.getElementById("crb-start-backup")
);

/** @type {HTMLButtonElement} - `Botón copiar comando sudo` */
const botonCopiar = /** @type {HTMLButtonElement} */ (
    document.getElementById("crb-copy-command")
);

/** @type {HTMLParagraphElement} - `Párrafo de progreso del backup` */
const parrafoProgreso = /** @type {HTMLParagraphElement} */ (
    document.getElementById("crb-progress")
);

/** @type {HTMLSpanElement} - `Texto del paso actual del progreso` */
const textoProgreso = /** @type {HTMLSpanElement} */ (
    document.getElementById("crb-progress-text")
);

/** @type {HTMLParagraphElement} - `Párrafo del resultado final` */
const parrafoResultado = /** @type {HTMLParagraphElement} */ (
    document.getElementById("crb-result")
);

/** @type {HTMLSelectElement} - `Selector de frecuencia del cron` */
const selectorFrecuencia = /** @type {HTMLSelectElement} */ (
    document.getElementById("crb-schedule")
);

/** @type {HTMLTableRowElement} - `Fila del día de la semana` */
const filaDiaSemana = /** @type {HTMLTableRowElement} */ (
    document.getElementById("crb-weekday-row")
);

/** @type {NodeListOf<HTMLButtonElement>} - `Botones de borrar backups` */
const botonesBorrar = document.querySelectorAll(".crb-delete");

/** @type {NodeListOf<HTMLButtonElement>} - `Botones de descargar a carpeta` */
const botonesDescargarCarpeta = document.querySelectorAll(".crb-download-folder");

/**
 * -------------------------------------------------
 * -----  `enviarPeticion(accion, datosExtra)`  -----
 * -------------------------------------------------
 * - Envía una petición POST a admin-ajax.php con el nonce del plugin.
 * @param {string} accion - Acción AJAX registrada en el plugin.
 * @param {Object<string, string>} [datosExtra] - Campos adicionales de la petición.
 * @return {Promise<Object>} Respuesta JSON del servidor.
 */
const enviarPeticion = async (accion, datosExtra = {}) => {

    //  -----  cuerpo de la petición con los campos base  -----
    const cuerpo = new URLSearchParams();

    //  -----  acción AJAX del plugin  -----
    cuerpo.append("action", accion);

    //  -----  nonce de seguridad de WordPress  -----
    cuerpo.append("_ajax_nonce", CRB_JS.nonce);

    //  -----  añadir cada campo extra al cuerpo  -----
    Object.entries(datosExtra).forEach(([clave, valor]) => {
        cuerpo.append(clave, valor);
    });

    //  -----  enviar la petición con las mismas credenciales del admin  -----
    const respuesta = await fetch(CRB_JS.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        body: cuerpo,
    });

    //  -----  devolver la respuesta como objeto JSON  -----
    return respuesta.json();
};

/**
 * ----------------------------
 * -----  `iniciarBackup()`  -----
 * ----------------------------
 * - Lanza un backup en segundo plano y empieza a consultar su estado.
 * @return {Promise<void>}
 */
const iniciarBackup = async () => {

    //  -----  lanzar el backup en el servidor  -----
    const respuesta = await enviarPeticion("crb_start_backup");

    //  -----  si falló, avisar y salir sin cambiar el botón  -----
    if (!respuesta.success) {
        mostrarResultado(respuesta.data.message || "No se pudo iniciar el backup.", true);
        return;
    }

    //  -----  desactivar el botón para no lanzar dos trabajos  -----
    botonCrear.disabled = true;

    //  -----  mostrar la zona de progreso  -----
    parrafoProgreso.classList.remove("hidden");

    //  -----  empezar a consultar el estado del trabajo  -----
    consultarEstado();
};

/**
 * -------------------------------
 * -----  `consultarEstado()`  -----
 * -------------------------------
 * - Consulta el estado del backup en curso y actualiza el progreso.
 * @return {Promise<void>}
 */
const consultarEstado = async () => {

    //  -----  pedir el estado actual del trabajo  -----
    const respuesta = await enviarPeticion("crb_get_status");

    //  -----  extraer los datos del estado  -----
    const estado = respuesta.data;

    //  -----  mostrar el texto del paso actual  -----
    textoProgreso.textContent = estado.message;

    //  -----  si sigue corriendo, volver a consultar en un momento  -----
    if (estado.state === "running") {
        window.setTimeout(consultarEstado, 2500);
        return;
    }

    //  -----  si terminó bien, avisar y recargar la lista  -----
    if (estado.state === "success") {
        mostrarResultado(
            estado.verification_ok
                ? "Backup completado y verificado."
                : "Backup completado, pero la verificación encontró problemas. Revisá el manifiesto.",
            !estado.verification_ok
        );
        window.setTimeout(() => window.location.reload(), 1500);
        return;
    }

    //  -----  si falló, avisar y recargar para liberar la interfaz  -----
    mostrarResultado(estado.error || "El backup falló.", true);
    window.setTimeout(() => window.location.reload(), 3000);
};

/**
 * ------------------------------------------------
 * -----  `mostrarResultado(texto, esError)`  -----
 * ------------------------------------------------
 * - Muestra el resultado final del backup en la página.
 * @param {string} texto - Mensaje a mostrar.
 * @param {boolean} esError - Si el mensaje es un error.
 * @return {void}
 */
const mostrarResultado = (texto, esError) => {

    //  -----  escribir el mensaje en el párrafo de resultado  -----
    parrafoResultado.textContent = texto;

    //  -----  aplicar el color según sea error o éxito  -----
    parrafoResultado.classList.toggle("crb-result-error", esError);

    //  -----  hacer visible el párrafo  -----
    parrafoResultado.classList.remove("hidden");
};

/**
 * ----------------------------
 * -----  `copiarComando()`  -----
 * ----------------------------
 * - Copia el comando sudo al portapapeles y confirma con el texto del botón.
 * @return {Promise<void>}
 */
const copiarComando = async () => {

    //  -----  si el botón no existe, salir  -----
    if (!botonCopiar) {
        return;
    }

    //  -----  destino del texto a copiar  -----
    const destino = botonCopiar.dataset.copyTarget;

    //  -----  elemento que contiene el comando  -----
    const fuente = /** @type {HTMLElement} */ (
        document.getElementById(destino)
    );

    //  -----  si no hay elemento, salir  -----
    if (!fuente) {
        return;
    }

    //  -----  intentar copiar con la API del portapapeles  -----
    try {
        await navigator.clipboard.writeText(fuente.textContent.trim());
    }
    //  -----  si el portapapeles falla, salir sin avisar  -----
    catch (error) {
        return;
    }

    //  -----  guardar el texto original del botón  -----
    const original = botonCopiar.textContent;

    //  -----  confirmar visualmente la copia  -----
    botonCopiar.textContent = "¡Copiado!";

    //  -----  restaurar el texto original al cabo de un momento  -----
    window.setTimeout(() => {
        botonCopiar.textContent = original;
    }, 2000);
};

/**
 * ---------------------------------
 * -----  `borrarBackup(boton)`  -----
 * ---------------------------------
 * - Borra un backup con confirmación en dos pasos dentro del propio botón.
 * @param {HTMLButtonElement} boton - Botón de borrado pulsado.
 * @return {Promise<void>}
 */
const borrarBackup = async (boton) => {

    //  -----  si el botón aún no está confirmado, pedir confirmación  -----
    if (boton.dataset.confirmed !== "1") {
        //  -----  marcar el botón como pendiente de confirmación  -----
        boton.dataset.confirmed = "1";

        //  -----  cambiar el texto y el color del botón  -----
        boton.textContent = "¿Seguro? Pulsa otra vez";
        boton.classList.add("crb-danger");

        //  -----  si no confirma, volver al estado normal al cabo de unos segundos  -----
        window.setTimeout(() => {
            boton.dataset.confirmed = "0";
            boton.textContent = "Borrar";
            boton.classList.remove("crb-danger");
        }, 4000);

        return;
    }

    //  -----  desactivar el botón mientras se borra  -----
    boton.disabled = true;

    //  -----  pedir el borrado al servidor  -----
    const respuesta = await enviarPeticion("crb_delete_backup", {
        stamp: boton.dataset.stamp,
    });

    //  -----  si falló, mostrar el mensaje de error  -----
    if (!respuesta.success) {
        mostrarResultado(respuesta.data.message || "No se pudo borrar el backup.", true);
        boton.disabled = false;
        return;
    }

    //  -----  recargar la tabla con el backup ya fuera  -----
    window.location.reload();
};

/**
 * -------------------------------------------------
 * -----  `construirUrlDescarga(tipo, stamp)`  -----
 * -------------------------------------------------
 * - Arma la URL autenticada de descarga de un archivo del backup.
 * @param {string} tipo - Tipo de archivo: db, files o manifest.
 * @param {string} stamp - Marca de tiempo del backup.
 * @return {string} - URL completa del archivo.
 */
const construirUrlDescarga = (tipo, stamp) => {

    //  -----  devolver la URL base con el tipo y el sello  -----
    return CRB_JS.downloadBase + "&type=" + encodeURIComponent(tipo) + "&stamp=" + encodeURIComponent(stamp);
};

/**
 * ------------------------------------------------
 * -----  `descargarConSelector(stamp)`  -----
 * ------------------------------------------------
 * - Abre el selector de carpetas del navegador y guarda ahí el backup completo.
 * @param {string} stamp - Marca de tiempo del backup.
 * @return {Promise<void>}
 */
const descargarConSelector = async (stamp) => {

    /** @type {FileSystemDirectoryHandle} - `Carpeta elegida por el usuario` */
    const carpetaDestino = await window.showDirectoryPicker();

    //  -----  nombre de la subcarpeta que agrupa el backup  -----
    const nombreCarpeta = "cafeteria-raiola_backup_" + stamp;

    //  -----  crear (o abrir) la subcarpeta dentro del destino elegido  -----
    const carpetaBackup = await carpetaDestino.getDirectoryHandle(nombreCarpeta, { create: true });

    //  -----  contador de archivos guardados  -----
    let guardados = 0;

    //  -----  recorrer los archivos que forman el backup  -----
    for (const archivo of ARCHIVOS_BACKUP) {

        //  -----  avisar el archivo en curso  -----
        mostrarResultado("Descargando " + archivo.tipo + " (" + (guardados + 1) + " de " + ARCHIVOS_BACKUP.length + ")…", false);

        //  -----  pedir el archivo con la sesión del administrador  -----
        const respuesta = await fetch(construirUrlDescarga(archivo.tipo, stamp), { credentials: "same-origin" });

        //  -----  si el manifiesto no existe, saltarlo sin cortar el resto  -----
        if (!respuesta.ok && archivo.tipo === "manifest") {
            continue;
        }

        //  -----  si falló un archivo esencial, cortar con error  -----
        if (!respuesta.ok) {
            throw new Error("No se pudo descargar " + archivo.tipo + ".");
        }

        //  -----  crear la entrada del archivo en la carpeta elegida  -----
        const manejador = await carpetaBackup.getFileHandle(
            archivo.tipo + "_" + stamp + "." + archivo.extension,
            { create: true }
        );

        //  -----  abrir el archivo para escribir los bytes  -----
        const escritor = await manejador.createWritable();

        //  -----  volcar el contenido recibido  -----
        await escritor.write(await respuesta.blob());

        //  -----  cerrar el archivo escrito  -----
        await escritor.close();

        //  -----  sumar uno al contador de guardados  -----
        guardados++;
    }

    //  -----  avisar el resultado final en la página  -----
    mostrarResultado(
        "Backup guardado en la carpeta elegida (" + guardados + " de " + ARCHIVOS_BACKUP.length + " archivos).",
        guardados !== ARCHIVOS_BACKUP.length
    );
};

/**
 * ---------------------------------------------
 * -----  `descargarACarpeta(boton)`  -----
 * ---------------------------------------------
 * - Descarga un backup completo al equipo eligiendo la carpeta de destino.
 * @param {HTMLButtonElement} boton - Botón de descarga pulsado.
 * @return {Promise<void>}
 */
const descargarACarpeta = async (boton) => {

    //  -----  marca de tiempo del backup a descargar  -----
    const stamp = boton.dataset.stamp;

    //  -----  desactivar el botón mientras dura la descarga  -----
    boton.disabled = true;

    //  -----  controlar los errores de la descarga  -----
    try {
        //  -----  si el navegador soporta el selector de carpetas, usarlo  -----
        if ("showDirectoryPicker" in window) {
            await descargarConSelector(stamp);
        }
        //  -----  si no, bajar el paquete combinado y que el navegador decida dónde  -----
        else {
            //  -----  navegar a la URL del paquete combinado  -----
            window.location.href = CRB_JS.packageBase + "&stamp=" + encodeURIComponent(stamp);
        }
    }
    //  -----  si se cerró el selector de carpetas, no es un error  -----
    catch (error) {
        //  -----  si se canceló la selección, quedarse en silencio  -----
        if (error.name === "AbortError") {
            boton.disabled = false;
            return;
        }

        //  -----  cualquier otro fallo se muestra en la página  -----
        mostrarResultado(error.message || "No se pudo descargar el backup.", true);
    }

    //  -----  volver a habilitar el botón  -----
    boton.disabled = false;
};

/**
 * --------------------------------------
 * -----  `actualizarFilaDiaSemana()`  -----
 * --------------------------------------
 * - Muestra u oculta la fila del día de la semana según la frecuencia.
 * @return {void}
 */
const actualizarFilaDiaSemana = () => {

    //  -----  si falta la fila o el selector, salir  -----
    if (!filaDiaSemana || !selectorFrecuencia) {
        return;
    }

    //  -----  mostrar la fila solo con frecuencia semanal  -----
    filaDiaSemana.classList.toggle("hidden", selectorFrecuencia.value !== "weekly");
};

/*
    *  ---------------------------------  *
    *  -----  Escuchas de eventos  -----  *
    *  ---------------------------------  *
*/

//  -----  si existe el botón de crear, enlazar su click  -----
if (botonCrear) {
    botonCrear.addEventListener("click", async (event) => {

        //  -----  evitar el comportamiento por defecto del botón  -----
        event.preventDefault();

        //  -----  lanzar el backup  -----
        await iniciarBackup();
    });
}

//  -----  si existe el botón de copiar, enlazar su click  -----
if (botonCopiar) {
    botonCopiar.addEventListener("click", copiarComando);
}

//  -----  si existe el selector de frecuencia, enlazar su cambio  -----
if (selectorFrecuencia) {
    selectorFrecuencia.addEventListener("change", actualizarFilaDiaSemana);
}

//  -----  enlazar el click de cada botón de borrado  -----
botonesBorrar.forEach((boton) => {
    boton.addEventListener("click", (event) => {

        //  -----  evitar el comportamiento por defecto del botón  -----
        event.preventDefault();

        //  -----  borrar el backup de ese botón  -----
        borrarBackup(boton);
    });
});

//  -----  enlazar el click de cada botón de descarga a carpeta  -----
botonesDescargarCarpeta.forEach((boton) => {
    boton.addEventListener("click", (event) => {

        //  -----  evitar el comportamiento por defecto del botón  -----
        event.preventDefault();

        //  -----  descargar el backup a la carpeta elegida  -----
        descargarACarpeta(boton);
    });
});

//  -----  si ya hay un backup en curso al entrar, seguir su progreso  -----
if (CRB_JS.statusRunning) {
    parrafoProgreso.classList.remove("hidden");
    consultarEstado();
}
