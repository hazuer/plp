// List of tracking numbers to process
const trackingNumbers = [
"46640394036518"
];
// Array para almacenar los resultados
const resultados = [];
// Función para enviar datos al endpoint
async function enviarDatos(resultado) {
    try {
        const endpoint = "https://paqueterialospinos.com/controllers/puppeteer.php";
        console.log(`📤 Enviando datos de ${resultado.tracking} al endpoint paqueterialospinos`);
        // Usando fetch desde el contexto del navegador
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
        return response; // <--- Devuelve el objeto completo
    } catch (error) {
        console.error("❌ Error al enviar datos:", error);
        return { success: "false", message: "Error de red o excepción" };
    }
}
let contador = 0;
const totalElementos = trackingNumbers.length;
for (const trackingNumber of trackingNumbers) {
    contador++;
    const resultado = {
        option:"store",
        id_location:1,
        phone:"",
        receiver:"",
        id_contact:1,
        tracking:trackingNumber,
        id_cat_parcel:2,
        id_marcador:"red",
        estado:""
    };

    try {
        await page.goto("https://ds.imile.com/");
        await page.waitForTimeout(2000);
        console.log(":::::::::::::::::::::::::::::::::::::::::::::::::::::::::");
        console.log(`:::::: Procesando ${contador} de ${totalElementos} ::::::`);
        console.log(":::::::::::::::::::::::::::::::::::::::::::::::::::::::::");

        await page.waitForSelector(`input[placeholder="Tracking..."]`, { timeout: 2000 });
        let input;
        try {
            // Intentar con el selector en inglés
            await page.waitForSelector(`input[placeholder="Tracking..."]`, { 
                visible: true,
                timeout: 5000 
            });
            input = await page.$(`input[placeholder="Tracking..."]`);
        } catch (error) {
            console.log('🔴 No se encontró el input en inglés, probando en español...');
        }
        // Interacción con el input
        try {
            console.log('🟡 Escribiendo número de seguimiento...');
            await input.click({ delay: 100 }); // Pequeña pausa para simular comportamiento humano
            // Opción 1: Usando page.type (más fiable para aplicaciones React)
            await input.type(trackingNumber);
            // Verificación
            const currentValue = await page.evaluate(el => el.value, input);
            if (currentValue !== trackingNumber) {
                await page.screenshot({ path: 'input-value-error.png' });
                throw new Error("El texto no se ingresó correctamente");
            }
            console.log("✅ Texto ingresado correctamente");
            // Presionar Enter
            console.log('🟡 Presionando Enter...');
            await input.press('Enter');
            // --- NUEVO CÓDIGO PARA PESTAÑA ---
            console.log('🟡 Esperando pestaña "Recipiente de información"...');
            await page.waitForTimeout(5000); // Espera más generosa
        } catch (error) {
            console.error('🔴 Error durante la interacción:', error);
        }
        /*try {
            // Opción 2: Usar XPath para texto exacto
            const [tab] = await page.$x('//button[contains(@class, "MuiTab-root") and normalize-space(text())="Recipiente de información"]');
            if (tab) {
                await tab.click();
                console.log('✅ Clic con XPath');
            } else {
                const [tab] = await page.$x('//button[contains(@class, "MuiTab-root") and normalize-space(text())="Cliente Info"]');
                if (tab) {
                    await tab.click();
                }
                throw new Error("No se encontró la pestaña con XPath");
            }

        } catch (error) {
            console.error('🔴 Error al interactuar con la pestaña:', error);
        }*/
       try {
            const possibleNames = [
                "Recipiente de información",
                "Cliente Info",
                "Customer Info"
            ];
            let tabFound = false;
            for (const name of possibleNames) {
                const [tab] = await page.$x(`//button[contains(@class, "MuiTab-root") and normalize-space(text())="${name}"]`);
                if (tab) {
                    await tab.click();
                    console.log(`✅ Clic en pestaña: ${name}`);
                    tabFound = true;
                    break;
                }
            }
            if (!tabFound) {
                throw new Error(`No se encontró la pestaña con ninguno de los nombres: ${possibleNames.join(', ')}`);
            }
        } catch (error) {
            console.error('🔴 Error al interactuar con la pestaña:', error);
        }
        await page.waitForTimeout(1000);

        let tel_entrante = null;
        let contact_name = null;

        /*try {
            const elements = await page.$$('.detail-item');
            for (const element of elements) {
                try {
                    const label = await element.$eval('.label', el => el.textContent.trim());
                    const value = await element.$eval('.value', el => el.textContent.trim());

                    if (label.includes('Teléfono entrante')) {
                        tel_entrante = value.replace(/\D/g, '').slice(-10);
                    }
                    if (label.includes('Contacto del destinatario')) {
                        contact_name = value;
                    }if (label.includes('Cliente Nombre')) {
                        contact_name = value;
                    }
                } catch (error) {
                    console.log('Error procesando elemento:', error);
                }
            }
        } catch (error) {
            console.error('Error al buscar elementos:', error);
        }*/
       try {
            const elements = await page.$$('.detail-item');
            for (const element of elements) {
                try {
                    const label = await element.$eval('.label', el => el.textContent.trim());
                    const value = await element.$eval('.value', el => el.textContent.trim());

                    // Mapeo de posibles variaciones para cada campo
                    const phoneLabels = ['Teléfono entrante', 'Customer phone'];
                    const nameLabels = ['Contacto del destinatario', 'Customer Name'];

                    // Normalizar la etiqueta para comparación (elimina acentos y convierte a minúsculas)
                    const normalizedLabel = label
                        .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // Elimina acentos
                        .toLowerCase();
                    // Buscar coincidencias para teléfono
                    if (phoneLabels.some(phoneLabel => 
                        normalizedLabel.includes(phoneLabel.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase()))) {
                        tel_entrante = value.replace(/\D/g, '').slice(-10);
                    }

                    // Buscar coincidencias para nombre
                    if (nameLabels.some(nameLabel => 
                        normalizedLabel.includes(nameLabel.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase()))) {
                        contact_name = value;
                    }

                } catch (error) {
                    console.log('Error procesando elemento:', error);
                }
            }
        } catch (error) {
            console.error('Error al buscar elementos:', error);
        }
                    // Usar los valores donde los necesites
            console.log('::::::::::::::::');
            console.log('::::::::::::::::');
            console.log('Datos extraídos:');
            console.log('- Teléfono:', tel_entrante);
            console.log('- Contacto:', contact_name);
            console.log('::::::::::::::::');
        // ========= VALIDACIÓN DE DATOS =========
        const errores = [];

        // Validar teléfono (10 dígitos exactos)
        if (!tel_entrante || !/^\d{10}$/.test(tel_entrante)) {
            errores.push('Teléfono inválido o no encontrado');
            tel_entrante = null; // Forzar a null si no cumple el formato
        }

        // Validar nombre (mínimo 3 caracteres con letras)
        if (!contact_name || !/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,}$/.test(contact_name)) {
            errores.push('Nombre de contacto inválido o no encontrado');
            contact_name = null;
        }

        // ========= ASIGNACIÓN Y ENVÍO =========
        if (errores.length === 0) {
            resultado.receiver = contact_name;
            resultado.phone = tel_entrante;
            console.log(`✅ Datos válidos: ${contact_name} | ${tel_entrante}`);
        
            try {
                // Envío al endpoint con timeout
                const respuestaServidor = await Promise.race([
                    enviarDatos(resultado),
                    new Promise((_, reject) => 
                        setTimeout(() => reject(new Error('Timeout excedido')), 10000)
                    )
                ]);

                if (respuestaServidor?.success === true || respuestaServidor?.success === "true") {
                    resultado.estado = "Registrado";
                    console.log('📌 Registro exitoso en el servidor');
                } else {
                    const msg = respuestaServidor?.message?.replace(/["']/g, "") || "Error sin especificar";
                    resultado.estado = "Falló: " + msg;
                    console.error('❌ Error del servidor:', msg);
                }
            } catch (error) {
                resultado.estado = "Falló: Error de conexión";
                console.error('🚨 Error al enviar al endpoint:', error.message);
            }
        } else {
            resultado.estado = "Falló: " + errores.join(' - ');
            console.error('❌ Datos incompletos:', errores.join(' | '));
            
            // Opcional: Tomar screenshot para diagnóstico
            await page.screenshot({ path: `error_${Date.now()}.png` });
        }

    } catch (error) {
        console.error(`❌ Error al procesar ${trackingNumber}:`, error.message);
        resultado.estado = `error: ${error.message}`;
    } finally {
        resultados.push(resultado);
        await page.waitForTimeout(1000);
    }
}
await page.waitForTimeout(500);
console.log("\n=== Proceso completado para todos los números de guía ===");
console.log("\n📊 RESULTADOS FINALES:");
console.log(JSON.stringify(resultados, null, 2));

