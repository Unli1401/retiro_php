// Configuración Firebase
const firebaseConfig = {
  apiKey: "AIzaSyAG-bhvl4ckjlZ04U9-Au_ga3GFFE0wab4",
  authDomain: "formulario-retiro-11427.firebaseapp.com",
  projectId: "formulario-retiro-11427",
  storageBucket: "formulario-retiro-11427.appspot.com",
  messagingSenderId: "191830176442",
  appId: "1:191830176442:web:b062935f74bbb16b400883",
  measurementId: "G-CSCB1EEYNH"
};

// Inicializar Firebase
firebase.initializeApp(firebaseConfig);
const db = firebase.firestore();

// ==================== FUNCIONES PRINCIPALES ====================
document.addEventListener('DOMContentLoaded', function() {
  // Configuración inicial
  document.getElementById('year').textContent = new Date().getFullYear();
  setupConditionalFields();
  setupFormNavigation();
  setupSuccessModal();
  setupStatusOptions();

  // Envío del formulario
  document.getElementById("retiroForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    try {
      // Validar todos los pasos
      if (!validateAllSteps()) return;

      // Mostrar loader
      const loader = document.createElement('div');
      loader.className = 'loader';
      this.appendChild(loader);

      // Obtener datos del formulario
      const formData = new FormData(this);
      const data = Object.fromEntries(formData.entries());
      
      // Procesar checkboxes
      const checkboxes = ['tiene_accesorios', 'retiro_masivo', 'desconectados', 'apilados', 'requiere_epp'];
      checkboxes.forEach(checkbox => {
        data[checkbox] = data[checkbox] === 'on' ? 'Sí' : 'No';
      });

      // 1. Guardar en Firebase
      await db.collection("checklists_retiro").add(data);

      // 2. Enviar a PHP
      const response = await fetch('http://localhost:8000/send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || "Error en el servidor");
      }
      
      const result = await response.json();
      if (!result.success) throw new Error(result.error || "Error al procesar");

      // Mostrar éxito
      showSuccessModal();
      this.reset();
      goToStep('contacto');

    } catch (error) {
      showGlobalError("Error al enviar: " + error.message);
      console.error("Detalles:", error);
    } finally {
      const loader = this.querySelector('.loader');
      if (loader) loader.remove();
    }
  });
});

// ==================== FUNCIONES AUXILIARES ====================
function setupSuccessModal() {
  // Configurar el botón de cerrar
  document.querySelector('.modal-close')?.addEventListener('click', function() {
    document.getElementById('successModal').classList.add('hidden');
  });
  
  // Configurar el botón Aceptar
  document.querySelector('.btn-close-modal')?.addEventListener('click', function() {
    document.getElementById('successModal').classList.add('hidden');
  });
}

function setupStatusOptions() {
  const radios = document.querySelectorAll('input[name="estado"]');
  radios.forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.status-option').forEach(option => {
        option.classList.remove('selected');
      });
      radio.closest('.status-option').classList.add('selected');
    });
  });
}

function showSuccessModal() {
  document.getElementById('successModal').classList.remove('hidden');
}

function showGlobalError(message) {
  const errorContainer = document.getElementById('global-error') || document.createElement('div');
  errorContainer.id = 'global-error';
  errorContainer.className = 'global-error-message';
  errorContainer.textContent = message;
  
  if (!document.getElementById('global-error')) {
    document.querySelector('.form-container').prepend(errorContainer);
  }
}

function clearGlobalError() {
  const errorContainer = document.getElementById('global-error');
  if (errorContainer) errorContainer.remove();
}

function validateAllSteps() {
  const steps = ['step-contacto', 'step-retiro', 'step-accesos'];
  let allValid = true;
  
  steps.forEach(stepId => {
    if (!validateStep(stepId)) {
      if (allValid) {
        goToStep(stepId.replace('step-', ''));
        showStepErrorMessage(stepId);
      }
      allValid = false;
    }
  });
  
  return allValid;
}

function showStepErrorMessage(stepId) {
  const messages = {
    'step-contacto': 'Complete los datos de contacto',
    'step-retiro': 'Complete los detalles del retiro',
    'step-accesos': 'Complete los requisitos de acceso'
  };
  showGlobalError(messages[stepId] || 'Error en el formulario');
}

function validateStep(stepId) {
  const step = document.getElementById(stepId);
  if (!step) return false;

  step.querySelectorAll('.error-message').forEach(el => el.remove());
  step.querySelectorAll('input, select, textarea').forEach(el => {
    el.style.borderColor = '';
  });

  let isValid = true;
  const inputs = step.querySelectorAll('[required]');

  inputs.forEach(input => {
    const value = input.value.trim();
    let error = '';
    
    if (input.type === 'radio') {
      const radioGroup = document.querySelectorAll(`input[type="radio"][name="${input.name}"]`);
      if (!Array.from(radioGroup).some(radio => radio.checked)) {
        error = 'Seleccione una opción';
      }
    } else if (!value) {
      error = 'Campo obligatorio';
    } else if (input.type === 'email' && !validateEmail(value)) {
      error = 'Correo inválido';
    } else if (input.type === 'date' && !validateDate(value)) {
      error = 'Fecha no válida';
    }
    
    if (error) {
      isValid = false;
      showFieldError(input, error);
    }
  });

  return isValid;
}

function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

function validateDate(dateString) {
  const inputDate = new Date(dateString);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return inputDate >= today;
}

function showFieldError(input, message) {
  input.style.borderColor = '#ff4444';
  const errorMsg = document.createElement('div');
  errorMsg.className = 'error-message';
  errorMsg.textContent = message;
  input.parentNode.insertBefore(errorMsg, input.nextSibling);
}

function setupConditionalFields() {
  document.getElementById('tiene_accesorios').addEventListener('change', function() {
    document.getElementById('accesorios_container').classList.toggle('hidden', !this.checked);
  });

  document.getElementById('retiro_masivo').addEventListener('change', function() {
    document.getElementById('masivo_container').classList.toggle('hidden', !this.checked);
  });

  document.getElementById('requiere_epp').addEventListener('change', function() {
    document.getElementById('epp_container').classList.toggle('hidden', !this.checked);
  });

  document.getElementById('estacionamiento').addEventListener('change', function() {
    document.getElementById('estacionamiento_info').classList.toggle('hidden', this.value !== 'si');
  });

  document.getElementById('destino').addEventListener('change', function() {
    document.getElementById('otro_destino_container').classList.toggle('hidden', this.value !== 'otro');
  });
}

function setupFormNavigation() {
  document.querySelectorAll('.btn-next').forEach(button => {
    button.addEventListener('click', function() {
      const currentStep = this.closest('.form-step');
      const nextStepId = this.dataset.next;
      if (validateStep(currentStep.id)) {
        clearGlobalError();
        goToStep(nextStepId);
      }
    });
  });
  
  document.querySelectorAll('.btn-prev').forEach(button => {
    button.addEventListener('click', function() {
      clearGlobalError();
      goToStep(this.dataset.prev);
    });
  });
}

function goToStep(stepId) {
  const fullStepId = `step-${stepId}`;
  document.querySelectorAll('.form-step').forEach(step => {
    step.classList.remove('active');
  });
  
  const stepElement = document.getElementById(fullStepId);
  if (stepElement) {
    stepElement.classList.add('active');
    updateProgressBar(stepId);
    document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
  }
}

function updateProgressBar(activeStep) {
  // Primero, quitar la clase active de todos los pasos
  document.querySelectorAll('.progress-step').forEach(step => {
    step.classList.remove('active');
  });
  
  // Activar los pasos según corresponda
  const steps = ['contacto', 'retiro', 'accesos'];
  const activeIndex = steps.indexOf(activeStep);
  
  if (activeIndex >= 0) {
    for (let i = 0; i <= activeIndex; i++) {
      const stepName = steps[i];
      document.querySelector(`.progress-step[data-step="${stepName}"]`)?.classList.add('active');
    }
  }
}