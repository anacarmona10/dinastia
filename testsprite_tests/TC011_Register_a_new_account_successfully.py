import asyncio
import re
from playwright import async_api
from playwright.async_api import expect

async def run_test():
    pw = None
    browser = None
    context = None

    try:
        # Start a Playwright session in asynchronous mode
        pw = await async_api.async_playwright().start()

        # Launch a Chromium browser in headless mode with custom arguments
        browser = await pw.chromium.launch(
            headless=True,
            args=[
                "--window-size=1280,720",
                "--disable-dev-shm-usage",
                "--ipc=host",
                "--single-process"
            ],
        )

        # Create a new browser context (like an incognito window)
        context = await browser.new_context()
        # Wider default timeout to match the agent's DOM-stability budget;
        # auto-waiting Playwright APIs (expect, locator.wait_for) inherit this.
        context.set_default_timeout(15000)

        # Open a new page in the browser context
        page = await context.new_page()

        # Interact with the page elements to simulate user flow
        # -> navigate
        await page.goto("http://localhost:8080/frontend/")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Open the registration page at /frontend/Registro.html (the 'Registro' page) and inspect the registration form fields.
        await page.goto("http://localhost:8080/frontend/Registro.html")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # -> Open the 'Seleccione un tipo de documento' dropdown and choose 'Cédula de Ciudadanía'.
        # Seleccione un tipo de documento Cédula de... dropdown
        elem = page.locator("xpath=/html/body/div/div[2]/div/form/select").nth(0)
        await elem.wait_for(state="visible", timeout=10000)
        await elem.select_option("")
        
        # -> Fill the registration fields (Nombre completo, Número de documento, Correo electrónico, Contraseña, Confirmar contraseña) and click the 'Registrar' button to submit the form.
        # Nombre completo text field
        elem = page.locator('[id="nombreCompleto"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("Test Usuario")
        
        # -> Fill the registration fields (Nombre completo, Número de documento, Correo electrónico, Contraseña, Confirmar contraseña) and click the 'Registrar' button to submit the form.
        # Número de documento text field
        elem = page.locator('[id="numeroDocumento"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("123456789")
        
        # -> Fill the registration fields (Nombre completo, Número de documento, Correo electrónico, Contraseña, Confirmar contraseña) and click the 'Registrar' button to submit the form.
        # Correo electrónico email field
        elem = page.locator('[id="correo"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("example@gmail.com")
        
        # -> Fill the registration fields (Nombre completo, Número de documento, Correo electrónico, Contraseña, Confirmar contraseña) and click the 'Registrar' button to submit the form.
        # Contraseña password field
        elem = page.locator('[id="contraseña"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("password123")
        
        # -> Fill the registration fields (Nombre completo, Número de documento, Correo electrónico, Contraseña, Confirmar contraseña) and click the 'Registrar' button to submit the form.
        # Registrar button
        elem = page.get_by_role('button', name='Registrar', exact=True)
        await elem.click(timeout=10000)
        
        # -> Fill the 'Contraseña' and 'Confirmar contraseña' fields with a valid password (e.g. Password123!) and click the 'Registrar' button to submit the form.
        # Contraseña password field
        elem = page.locator('[id="contraseña"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("Password123!")
        
        # -> Fill the 'Contraseña' and 'Confirmar contraseña' fields with a valid password (e.g. Password123!) and click the 'Registrar' button to submit the form.
        # Confirmar contraseña password field
        elem = page.locator('[id="confirm_password"]')
        await elem.wait_for(state="visible", timeout=10000)
        await elem.fill("Password123!")
        
        # -> Fill the 'Contraseña' and 'Confirmar contraseña' fields with a valid password (e.g. Password123!) and click the 'Registrar' button to submit the form.
        # Registrar button
        elem = page.get_by_role('button', name='Registrar', exact=True)
        await elem.click(timeout=10000)
        
        # --> Assertions to verify final state
        
        # --> The app navigated to the email verification page indicating a verification code was sent.
        # Assert-outcome: passed
        # Assert: The browser is on the verification page URL.
        await expect(page).to_have_url(re.compile("/frontend/verificacion\\.html"), timeout=15000), "The browser is on the verification page URL."
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    