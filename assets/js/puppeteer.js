// List of tracking numbers to process
const trackingNumbers = [
"JMX300443207219"
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
        return true;
    } catch (error) {
        console.error("❌ Error al enviar datos:", error);
        return false;
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
            // id_contact:0,
            tracking:trackingNumber,
            id_cat_parcel:1,
            id_marcador:"",
            estado:""
        };
    try {
        await page.goto("https://jmx.jtjms-mx.com/app/serviceQualityIndex/recordSheet?title=Orden%20de%20registro&moduleCode=");
        await page.waitForTimeout(2000);
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
        console.log(`:::::: Procesando ${contador} de ${totalElementos} ::::::`);
        const currentValue = await page.evaluate(el => el.value, input);
        if (currentValue !== trackingNumber) {
            throw new Error("Error al pegar el texto");
        }
        console.log("✅ Texto pegado correctamente");
        // Wait and click "Información básica" tab
        await page.waitForTimeout(500);
        await page.waitForSelector("#tab-base.el-tabs__item", { timeout: 500 });
        await page.click("#tab-base.el-tabs__item");
        console.log(`✅ Pestaña "Información básica" clickeada`);
        await page.waitForTimeout(1000);
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
        await page.waitForTimeout(800);
        // Extract receiver information
        await page.waitForSelector(".item .row", { timeout: 2500 });
        const [nameR, telR] = await page.evaluate(() => {
            const rows = Array.from(document.querySelectorAll(".item .row"));
            const nameRow = rows.find(row => row.textContent.includes("Nombre del receptor:"));
            const telRow = rows.find(row => row.textContent.includes("Teléfono del destinatario:"));
            const nameR = nameRow ? nameRow.querySelector("span").textContent.trim() : "";
            let telR = telRow ? telRow.querySelector("span").textContent.trim() : "";
            telR = telR.slice(-10);
            return [nameR, telR];
        });
        // Guardar datos en el objeto resultado
        resultado.receiver = nameR;
        resultado.phone = telR;
        console.log(`✅ Datos extraídos: ${nameR} | ${telR}`);
        // Enviar datos al endpoint inmediatamente después de extraerlos
        const envioExitoso = await enviarDatos(resultado);
        resultado.estado = envioExitoso ? "Registrado" : "Procesado pero falló envío";
    } catch (error) {
        console.error(`❌ Error al procesar ${trackingNumber}:`, error.message);
        resultado.estado = `error: ${error.message}`;
    } finally {
        resultados.push(resultado);
        await page.waitForTimeout(1000);
    }
    //contador++;
    //console.info(`Procesando ${contador} de ${totalElementos}`);
}
console.log("\n=== Proceso completado para todos los números de guía ===");
console.log("\n📊 RESULTADOS FINALES:");
console.log(JSON.stringify(resultados, null, 2));