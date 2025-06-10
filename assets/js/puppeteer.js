// Solicitar los números de seguimiento mediante un prompt
const input = prompt("👾 Ingresa los números de guía J&T [📦]:");
// Procesar el input para crear el array
const trackingNumbers = input 
    ? input.split('\n')          // Dividir por saltos de línea
           .map(num => num.trim()) // Limpiar espacios
           .filter(num => num !== '') // Eliminar líneas vacías
    : []; // Si no se ingresa nada, array vacío
const color = prompt(`
👾 Ingresa un color (elige una opción) [🎨]:
---------------------------------
🔴 red    🟢 green
💙 blue   ⚫ black
---------------------------------`).trim().toLowerCase() || "black";
// Validación implícita (si el color no está en la lista, usa "black")
const coloresValidos = ["red", "green", "blue", "black"];
const colorFinal = coloresValidos.includes(color) ? color : "black";
// Solicitar ubicación con opciones claras
const id_location = prompt(`
👾 Ingresa el ID de ubicación [📍]:
1 - TQL
2 - ZAC`) || 1;

// Solicitar ID de usuario con opciones
const id_user = prompt(`
👾 Ingresa el ID de usuario [👤]:
2 - karen
4 - josue`) || 1;

const hours = prompt(`
👾 Ingresa el número de horas [🕟]:
0 - Para indicar la hora de inicio de registro actual
>0 - Para modificar la fecha de registro
`) || 0;

// Generar mensaje de confirmación
const guiaInicial = trackingNumbers[0] || "N/A";
const guiaFinal = trackingNumbers[trackingNumbers.length - 1] || "N/A";
const totalGuias = trackingNumbers.length;

const mensajeConfirmacion = `
👾 Configuración ingresada [⚙️]:
---------------------------------
🔢 Total de guías: ${totalGuias}
📦 Guía inicial: ${guiaInicial}
📦 Guía final: ${guiaFinal}
---------------------------------
🎨 Color: ${colorFinal}
📍 Ubicación: ${id_location} ${id_location == 1 ? "TQL" : "ZAC"}
👤 Usuario: ${id_user} ${id_user == 2 ? "karen" : "josue"}
🕟 Horas: ${hours}
---------------------------------
¿👾 Los datos son correctos?`;

// Mostrar alerta de confirmación
const isConfirmed = confirm(mensajeConfirmacion);

// Endpoint function
async function enviarDatos(resultado) {
    try {
        const endpoint = "https://paqueterialospinos.com/controllers/puppeteer.php";
        console.log(`📤 Enviando datos de ${resultado.tracking} al endpoint paqueterialospinos`);
        const response = await page.evaluate(async (url, data) => {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        }, endpoint, resultado);
        console.log("✅ Respuesta del servidor:", response);
        return response;
    } catch (error) {
        console.error("❌ Error al enviar datos:", error);
        return { success: "false", message: "Error de red o excepción" };
    }
}

// Array to store all results
const resultados = [];
let contador = 0;
const totalElementos = trackingNumbers.length;

if (isConfirmed) {
    for (const trackingNumber of trackingNumbers) {
        contador++;
        const resultado = {
            option:"store",
            id_location:id_location,
            phone:"",
            receiver:"",
            id_user:id_user,
            tracking:trackingNumber,
            id_cat_parcel:1, //JMX
            id_marcador:colorFinal,
            estado:"",
            hours:hours
        };
        try {
            await page.goto("https://jmx.jtjms-mx.com/app/serviceQualityIndex/recordSheet?title=Orden%20de%20registro&moduleCode=");
            await page.waitForTimeout(2300);
            try {
                await page.waitForSelector(`input[placeholder="Por favor, ingrese"]`, { timeout: 2000 });
            } catch {
                console.log("No se encontró el input en español, recargando...");
                await page.reload();
                await page.waitForSelector(`input[placeholder="Por favor, ingrese"]`, { timeout: 3000 });
            }
            const input = await page.$(`input[placeholder="Por favor, ingrese"]`);
            await input.click();
            await page.evaluate((inputElement, text) => {
                inputElement.value = text;
                const event = new Event("input", { bubbles: true });
                inputElement.dispatchEvent(event);
            }, input, trackingNumber);
            console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
            console.log(`:::::::::::::::::: Procesando ${contador} de ${totalElementos} ::::::::::::::::::`);
            console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
            const currentValue = await page.evaluate(el => el.value, input);
            if (currentValue !== trackingNumber) {
                throw new Error("Error al pegar el texto");
            }
            console.log("✅ Texto pegado correctamente");
            // Wait and click "Información básica" tab
            await page.waitForTimeout(600);
            await page.waitForSelector("#tab-base.el-tabs__item", { timeout: 800 });
            await page.click("#tab-base.el-tabs__item");
            console.log(`✅ Pestaña "Información básica" clickeada`);
            await page.waitForTimeout(800);
            // Click all info icons
            try {
                await page.waitForSelector(".iconfuwuzhiliang-mingwen", { timeout: 800 });
                const icons = await page.$$(".iconfuwuzhiliang-mingwen");
                console.log(`🔍 Íconos encontrados: ${icons.length}`);
                for (let i = 0; i < icons.length; i++) {
                    try {
                        await icons[i].hover();
                        await icons[i].click();
                        await page.waitForTimeout(200);
                        console.log(`✅ Ícono ${i + 1} clickeado`);
                    } catch (error) {
                        console.warn(`⚠️ Error al hacer clic en ícono ${i + 1}:`, error.message);
                    }
                }
            } catch (error) {
                console.error("❌ No se encontraron íconos:", error.message);
            }
            await page.waitForTimeout(100);
            await page.waitForSelector(".item .row", { timeout: 2800 });
            const [nameR, telR] = await page.evaluate(() => {
                const rows    = Array.from(document.querySelectorAll(".item .row"));
                const nameRow = rows.find(row => row.textContent.includes("Nombre del receptor:"));
                const telRow  = rows.find(row => row.textContent.includes("Teléfono del destinatario:"));
                const nameR   = nameRow ? nameRow.querySelector("span").textContent.trim() : "";
                let telR      = telRow ? telRow.querySelector("span").textContent.trim() : "";
                telR          = telR.slice(-10);
                return [nameR, telR];
            });
            // Validación de datos antes del envío
            let datosValidos = true;
            if (!nameR || nameR.trim() === "") {
                console.log("❌ Nombre del receptor está vacío - No se enviará al endpoint");
                datosValidos = false;
                resultado.estado = "Falló: Nombre receptor vacío";
            }
            if (telR.includes("*")) {
                console.log("❌ Teléfono contiene asteriscos - No se enviará al endpoint");
                datosValidos = false;
                resultado.estado = "Falló: Teléfono con asteriscos";
            }
            if (!/^\d{10}$/.test(telR)) {
                console.log("❌ Teléfono no tiene 10 dígitos - No se enviará al endpoint");
                datosValidos = false;
                resultado.estado = "Falló: Teléfono inválido";
            }
            resultado.receiver = nameR;
            resultado.phone = telR;
            if (datosValidos) {
                console.log(`✅ Datos válidos: ${nameR} | ${telR}`);
                try {
                    const respuestaServidor = await enviarDatos(resultado);
                    if (respuestaServidor.success === "true") {
                        resultado.estado = "Registrado";
                    } else {
                        const msg = respuestaServidor.message || "Sin mensaje del servidor";
                        resultado.estado = "Falló: " + msg.replace(/["']/g, "");
                    }
                } catch (error) {
                    resultado.estado = "Falló: Error de conexión";
                    console.error("Error al enviar datos:", error);
                }
            } else {
                console.log(`⏸️ Datos no enviados: ${nameR} | ${telR} - Motivo: ${resultado.estado}`);
            }
        } catch (error) {
            console.error(`❌ Error al procesar ${trackingNumber}:`, error.message);
            resultado.estado = `Falló: ${error.message}`;
        } finally {
            resultados.push(resultado);
            await page.waitForTimeout(1000);
        }
    } // end for
    await page.waitForTimeout(500);
    console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
    console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
    console.log("📊 FIN DEL PROCESO:");
    // Filtrar y contar resultados
    const guiasRegistradas = resultados.filter(r => r.estado === "Registrado");
    const guiasConError    = resultados.filter(r => r.estado !== "Registrado" && r.estado.includes("Falló")); // Asegura que solo cuente los fallos reales
    console.log(`📦 Total procesado: ${resultados.length}`);
    console.log(`✅ Guías registradas correctamente: ${guiasRegistradas.length}`);
    if (guiasConError.length > 0) {
        console.log(`❌ Guías con errores: ${guiasConError.length}`);
        console.log("\n🔍 Detalle de errores:");
        guiasConError.forEach((resultado, index) => {
            console.log(`\n${index + 1}. Guía: ${resultado.tracking}`);
            console.log(`Estado: ${resultado.estado}`);
            console.log(`Receptor: ${resultado.receiver || "No disponible"}`);
            console.log(`Teléfono: ${resultado.phone || "No disponible"}`);
        });
    }
} else {
    console.log("❌ Proceso cancelado por el usuario");
}