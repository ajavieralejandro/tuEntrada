<!DOCTYPE html>
<html lang="es" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Comprar Entradas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- Navbar con logo -->
  <nav class="bg-white shadow p-4 flex items-center">
    <img
      src="https://upload.wikimedia.org/wikipedia/commons/a/a7/React-icon.svg"
      alt="Logo"
      class="h-10 w-auto"
    />
  </nav>

  <!-- Contenedor principal -->
  <div class="flex flex-col md:flex-row h-screen">

    <!-- Imagen izquierda -->
  <div class="relative w-full aspect-[3/2] md:aspect-[4/3] lg:aspect-[16/9] overflow-hidden rounded-lg shadow-md">
  <img
    src="{{ asset('storage/images/peteco.jpg') }}"
    alt="Evento"
    class="absolute inset-0 w-full h-full object-cover object-top"
  />
</div>

    <!-- Formulario derecha -->
    <div class="md:w-1/2 w-full bg-white p-8 overflow-auto flex flex-col">

      <h1 class="text-3xl font-bold mb-6">Compra tu entrada</h1>

      <form id="form-compra" method="POST" action="{{ route('entradas.procesar') }}" class="flex-grow flex flex-col" onkeydown="return evitarSubmitEnter(event)">
        @csrf

        <!-- Botón Volver (visible según paso) -->
        <button type="button"
                id="btn-volver"
                class="mb-4 text-blue-600 hover:text-blue-800 font-medium self-start hidden"
                onclick="volverCantidad()"
        >
          ← Volver
        </button>

        <!-- Paso 1: Selección cantidad -->
        <div id="paso-cantidad" class="mb-4">
          <label for="cantidad" class="block text-gray-700 font-semibold mb-1">
            ¿Cuántas entradas vas a comprar?
          </label>
       <input
  type="number"
  id="cantidad"
  name="cantidad"
  min="1"
  max="10"
  required
  class="w-full border border-black rounded-lg shadow-sm p-2"
  onchange="iniciarFormulario()"
/>
        </div>

        <!-- Paso 2: Ingreso personas -->
        <div id="paso-personas" class="space-y-4 hidden flex-grow flex flex-col justify-start">
          <h3 id="titulo-persona" class="text-lg font-semibold"></h3>
          <input
            type="text"
            id="nombre"
            placeholder="Nombre"
            class="w-full p-2 border rounded"
            required
          />
          <input
            type="email"
            id="email"
            placeholder="Email"
            class="w-full p-2 border rounded"
            required
          />
          <input
            type="text"
            id="dni"
            placeholder="DNI"
            class="w-full p-2 border rounded"
            required
          />

          <button
            type="button"
            onclick="guardarPersona()"
            class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 self-start"
          >
            Siguiente
          </button>
        </div>

        <!-- Paso 3: Desglose antes de pagar -->
        <div id="paso-desglose" class="hidden flex flex-col flex-grow">

          <h2 class="text-xl font-semibold mb-4">Resumen de la compra</h2>

          <div class="mb-4 p-4 border rounded bg-gray-50">
            <p><strong>Cantidad de entradas:</strong> <span id="res-cantidad"></span></p>
            <p><strong>Precio unitario:</strong> $<span id="res-precio-unitario"></span></p>
            <p class="mt-2 text-lg font-bold">Total: $<span id="res-total"></span></p>
          </div>

          <h3 class="font-semibold mb-2">Datos de las personas</h3>
          <ul id="res-personas" class="list-disc list-inside mb-6 max-h-48 overflow-auto border p-3 rounded bg-gray-50"></ul>

          <div id="btn-pagar" class="mt-auto block">
            <button
              type="submit"
              class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2"
            >
              <!-- Icono Mercado Pago SVG -->
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path fill="#00A650" d="M2 3h20v18H2z"/>
                <path fill="#FFF" d="M7 7h10v10H7z"/>
                <path fill="#00A650" d="M8 8h8v8H8z"/>
              </svg>
              Pagar con Mercado Pago
            </button>
          </div>
        </div>

        <!-- Inputs ocultos personas para enviar al backend -->
        <div id="personas-datos"></div>

      </form>
    </div>
  </div>

  <script>
    const precioUnitario = 500; // Cambiar según corresponda
    let cantidad = 0;
    let actual = 1;
    let personas = [];

    function evitarSubmitEnter(e) {
      if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit' && e.target.type !== 'button') {
        e.preventDefault();
        return false;
      }
      return true;
    }

    function iniciarFormulario() {
      cantidad = parseInt(document.getElementById('cantidad').value);
      if (!cantidad || cantidad < 1) {
        alert('Por favor, seleccioná una cantidad válida');
        return;
      }
      actual = 1;
      personas = [];
      document.getElementById('personas-datos').innerHTML = '';

      // Mostrar paso personas, ocultar cantidad y desglose
      document.getElementById('paso-cantidad').classList.add('hidden');
      document.getElementById('paso-personas').classList.remove('hidden');
      document.getElementById('paso-desglose').classList.add('hidden');
      document.getElementById('btn-pagar').classList.add('hidden');
      document.getElementById('btn-volver').classList.remove('hidden');

      mostrarPersona();
    }

    function mostrarPersona() {
      document.getElementById('titulo-persona').textContent = `Persona ${actual}`;
      document.getElementById('nombre').value = '';
      document.getElementById('email').value = '';
      document.getElementById('dni').value = '';
    }

    function guardarPersona() {
      const nombre = document.getElementById('nombre').value.trim();
      const email = document.getElementById('email').value.trim();
      const dni = document.getElementById('dni').value.trim();

      if (!nombre || !email || !dni) {
        alert('Por favor, completá todos los campos.');
        return;
      }

      personas.push({ nombre, email, dni });

      const divDatos = document.getElementById('personas-datos');
      divDatos.innerHTML += `
        <input type="hidden" name="personas[${actual}][nombre]" value="${nombre}">
        <input type="hidden" name="personas[${actual}][email]" value="${email}">
        <input type="hidden" name="personas[${actual}][dni]" value="${dni}">
      `;

      if (actual < cantidad) {
        actual++;
        mostrarPersona();
      } else {
        // Mostrar desglose y ocultar ingreso personas
        document.getElementById('paso-personas').classList.add('hidden');
        document.getElementById('paso-desglose').classList.remove('hidden');
        document.getElementById('btn-volver').classList.add('hidden');
        document.getElementById('btn-pagar').classList.remove('hidden');

        mostrarDesglose();
      }
    }

    function mostrarDesglose() {
      document.getElementById('res-cantidad').textContent = cantidad;
      document.getElementById('res-precio-unitario').textContent = precioUnitario.toFixed(2);
      document.getElementById('res-total').textContent = (cantidad * precioUnitario).toFixed(2);

      const ul = document.getElementById('res-personas');
      ul.innerHTML = '';
      personas.forEach((p, index) => {
        ul.innerHTML += `<li>${index + 1}. ${p.nombre} — ${p.email} — DNI: ${p.dni}</li>`;
      });
    }

    function volverCantidad() {
      // Reiniciar todo y volver a cantidad
      personas = [];
      actual = 1;
      cantidad = 0;
      document.getElementById('personas-datos').innerHTML = '';

      // Mostrar cantidad, ocultar personas y desglose
      document.getElementById('paso-cantidad').classList.remove('hidden');
      document.getElementById('paso-personas').classList.add('hidden');
      document.getElementById('paso-desglose').classList.add('hidden');
      document.getElementById('btn-pagar').classList.add('hidden');
      document.getElementById('btn-volver').classList.add('hidden');

      // Vaciar input cantidad para modificar
      document.getElementById('cantidad').value = '';
    }
  </script>
</body>
</html>
