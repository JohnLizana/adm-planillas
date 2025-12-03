// Asegúrate de que este código esté dentro de document.addEventListener('DOMContentLoaded', () => { ... });

    // LÓGICA PARA EL FORMULARIO DESPLEGABLE
    const toggleAddFormButton = document.getElementById('toggle-add-form');
    const addRecordFormContainer = document.getElementById('add-record-form-container');

    if (toggleAddFormButton && addRecordFormContainer) {
        // Por defecto, el formulario estará oculto con la clase 'collapsed'
        // Si hay un mensaje de éxito o error (después de un POST), lo mostramos.
        const hasMessages = document.querySelector('.message.success-message') || document.querySelector('.message.error-message');
        if (hasMessages) {
            addRecordFormContainer.classList.remove('collapsed');
            toggleAddFormButton.textContent = 'Ocultar Formulario';
        }

        toggleAddFormButton.addEventListener('click', () => {
            if (addRecordFormContainer.classList.contains('collapsed')) {
                addRecordFormContainer.classList.remove('collapsed');
                toggleAddFormButton.textContent = 'Ocultar Formulario';
            } else {
                addRecordFormContainer.classList.add('collapsed');
                toggleAddFormButton.textContent = 'Mostrar Formulario';
            }
        });
    }

    // ... (resto de tus scripts existentes) ...
        /* Función de Control del Sidebar */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    
    // ESTA LÍNEA ES CRÍTICA: Añade o quita la clase 'open'
    sidebar.classList.toggle('open');
}

// Cierra el Sidebar si se hace clic fuera de él (solo aplica en móviles)
window.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.querySelector('.menu-btn');

    // Asegura que el clic no fue en el sidebar ni en el botón de menú
    if (sidebar && sidebar.classList.contains('open') && 
        !sidebar.contains(event.target) && 
        !menuBtn.contains(event.target) && 
        window.innerWidth <= 768) 
    {
        sidebar.classList.remove('open');
    }
});
// Asegúrate de que este script esté incluido DESPUÉS del HTML
// El resto de tu código JS (ej. toggleDropdown) debe ir aquí...




// Función para desplegar/ocultar el Dropdown
function toggleDropdown() {
    document.getElementById("planillaDropdown").classList.toggle("show");
}

// Función principal para crear la planilla
document.addEventListener('DOMContentLoaded', (event) => {
    
    // Obtener el ID del usuario desde el cuerpo (body) del documento
    const userId = document.body.getAttribute('data-user-id');
    
    const planillaLinks = document.querySelectorAll('.create-planilla-link');

    planillaLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Evita que el enlace navegue

            const tipoPlanilla = this.getAttribute('data-tipo');
            
            // Ocultar el dropdown inmediatamente después del clic
            toggleDropdown(); 

            if (!userId) {
                console.error("Error: El ID del usuario no está disponible.");
                alert("Error: ID de usuario no encontrado. Por favor, vuelva a iniciar sesión.");
                return;
            }

            // 1. Preparar los datos
            const data = new URLSearchParams();
            data.append('tipo', tipoPlanilla);
            data.append('creador_id', userId);
            
            // 2. Enviar la solicitud POST
            fetch('crear_planilla.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json()) // Esperamos una respuesta JSON
            .then(result => {
                if (result.success) {
                    alert(`Planilla de ${tipoPlanilla} creada exitosamente. ID: ${result.id}`);
                    // Aquí podrías recargar la lista de pedidos o redirigir
                } else {
                    alert(`Error al crear planilla: ${result.message}`);
                }
            })
            .catch(error => {
                console.error('Error de conexión:', error);
                alert('Ocurrió un error al comunicarse con el servidor.');
            });
        });
    });
});
// Asegúrate de que este bloque reemplace la lógica anterior del Modo Oscuro en script.js

const toggle = document.getElementById('dark-mode-toggle');
const body = document.body;

// Función central que añade o quita la clase y guarda la preferencia
function applyTheme(isDark) {
    if (isDark) {
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
    } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
    }
    // Asegura que el estado del switch visual coincida con el estado aplicado
    if (toggle) {
        toggle.checked = isDark;
    }
}

// 1. Cargar la preferencia al inicio (Initialization)
const savedTheme = localStorage.getItem('theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
let initialThemeIsDark = false;

// Determinar el tema inicial
if (savedTheme) {
    initialThemeIsDark = savedTheme === 'dark';
} else {
    // Si no hay preferencia guardada, usamos la preferencia del sistema operativo
    initialThemeIsDark = prefersDark;
}

// Aplicar el tema inmediatamente después de la carga
applyTheme(initialThemeIsDark);


// 2. Manejar el evento de cambio del switch
if (toggle) { // Asegura que el elemento exista antes de añadir el listener
    toggle.addEventListener('change', function() {
        // Llama a applyTheme basándose en el nuevo estado del switch
        applyTheme(this.checked);
    });
}