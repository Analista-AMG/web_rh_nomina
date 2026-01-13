<!-- MODAL VER (Solo Lectura) -->
<div id="view-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 transition-opacity" onclick="closeModal('view-modal')" style="backdrop-filter: blur(5px);"></div>
        <div class="relative z-10 w-full transform overflow-hidden rounded-xl bg-white dark:bg-dark-card text-left shadow-2xl border border-light-border dark:border-dark-border" style="max-width: 1000px;">
            <div class="bg-white dark:bg-dark-card px-8 py-8">
                <div class="flex justify-between items-center mb-6 border-b border-light-border dark:border-dark-border pb-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Detalle de Persona</h3>
                    <button onclick="closeModal('view-modal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-times text-2xl"></i></button>
                </div>
                <form id="view-form">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div><label class="block text-sm font-semibold text-gray-500 mb-2">Tipo Doc.</label><input type="text" id="view-tdoc" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                        <div><label class="block text-sm font-semibold text-gray-500 mb-2">N° Documento</label><input type="text" id="view-doc" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                        <div><label class="block text-sm font-semibold text-gray-500 mb-2">Nacionalidad</label><input type="text" id="view-nacionalidad" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div><label class="block text-sm font-semibold text-gray-500 mb-2">Ap. Paterno</label><input type="text" id="view-paterno" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                        <div><label class="block text-sm font-semibold text-gray-500 mb-2">Ap. Materno</label><input type="text" id="view-materno" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                        <div><label class="block text-sm font-semibold text-gray-500 mb-2">Nombres</label><input type="text" id="view-nombres" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-6 mb-6">
                        <div class="flex-1"><label class="block text-sm font-semibold text-gray-500 mb-2">Fecha Nacimiento</label><input type="text" id="view-nac" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                        <div class="flex-1"><label class="block text-sm font-semibold text-gray-500 mb-2">Género</label><input type="text" id="view-genero" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-6 mb-6">
                        <div class="flex-1"><label class="block text-sm font-semibold text-gray-500 mb-2">Correo Personal</label><input type="text" id="view-correo-pers" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                        <div class="flex-1"><label class="block text-sm font-semibold text-gray-500 mb-2">Correo Corporativo</label><input type="text" id="view-correo-corp" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                    </div>
                    <div class="mb-2"><label class="block text-sm font-semibold text-gray-500 mb-2">Dirección</label><input type="text" id="view-direccion" readonly class="w-full bg-gray-50 dark:bg-gray-700 dark:text-gray-300 border-transparent rounded-lg py-2.5 px-4 cursor-default"></div>
                </form>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 px-8 py-5 flex flex-row-reverse border-t border-light-border dark:border-dark-border">
                <button type="button" onclick="closeModal('view-modal')" class="bg-white dark:bg-dark-card text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 px-6 py-2.5 rounded-lg font-bold hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">Cerrar</button>
            </div>
        </div>
    </div>
</div>
