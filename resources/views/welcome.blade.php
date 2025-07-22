<!DOCTYPE html>
<html lang="es" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Comprar Entradas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<!-- ... (head y estilos iguales) -->
<body class="bg-gray-100 min-h-screen">
  <div class="flex flex-col md:flex-row h-screen">
    <!-- Imagen -->
    <div class="relative w-full aspect-[3/2] md:aspect-[4/3] lg:aspect-[16/9] overflow-hidden shadow-md">
      <img
        src="{{ asset('storage/images/peteco.jpg') }}"
        alt="Evento"
        class="absolute inset-0 w-full h-full object-cover object-top"
      />
    </div>

    <!-- Formulario -->
    <div class="md:w-1/2 w-full bg-white p-8 overflow-auto flex flex-col">
      <h1 class="text-3xl font-bold mb-6">Compra tu entrada</h1>

      <form id="form-compra" method="POST" action="{{ route('entradas.procesar') }}" onkeydown="return evitarSubmitEnter(event)" class="flex flex-col flex-grow">
        @csrf

        <!-- Selección de cantidad -->
        <div class="mb-4">
          <label for="cantidad" class="block text-gray-700 font-semibold mb-1">¿Cuántas entradas vas a comprar?</label>
          <input
            type="number"
            id="cantidad"
            name="cantidad"
            min="1"
            max="10"
            required
            class="w-full border border-black rounded-lg shadow-sm p-2"
          />
        </div>

        <!-- Datos del comprador -->
        <div class="space-y-4 mb-4">
          <label class="block text-gray-700 font-semibold">Datos del comprador</label>
          <input type="text" name="nombre" placeholder="Nombre" class="w-full p-2 border rounded" required />
          <input type="email" name="email" placeholder="Email" class="w-full p-2 border rounded" required />
          <input type="text" name="dni" placeholder="DNI" class="w-full p-2 border rounded" required />
        </div>

        <!-- Resumen -->
        <div class="mb-6 p-4 border rounded bg-gray-50 hidden" id="resumen">
          <h2 class="text-xl font-semibold mb-2">Resumen de la compra</h2>
          <p><strong>Cantidad:</strong> <span id="res-cantidad"></span></p>
          <p><strong>Precio unitario:</strong> $<span id="res-precio"></span></p>
          <p class="mt-2 font-bold text-lg">Total: $<span id="res-total"></span></p>
        </div>

        <!-- Botón de pagar -->
        <button
          type="submit"
          class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition mt-auto flex items-center justify-center gap-2"
          onclick="return mostrarResumen()"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path fill="#00A650" d="M2 3h20v18H2z"/>
            <path fill="#FFF" d="M7 7h10v10H7z"/>
            <path fill="#00A650" d="M8 8h8v8H8z"/>
          </svg>
          Pagar con Mercado Pago
        </button>
      </form>
    </div>
  </div>

  <script>
    const precio = 500;

    function evitarSubmitEnter(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        return false;
      }
      return true;
    }

    function mostrarResumen() {
      const cantidad = parseInt(document.getElementById('cantidad').value);
      if (!cantidad || cantidad < 1) {
        alert("Por favor, ingresá una cantidad válida.");
        return false;
      }

      document.getElementById('res-cantidad').textContent = cantidad;
      document.getElementById('res-precio').textContent = precio.toFixed(2);
      document.getElementById('res-total').textContent = (cantidad * precio).toFixed(2);
      document.getElementById('resumen').classList.remove('hidden');

      return true; // Permite enviar el formulario
    }
  </script>
</body>
</html>
