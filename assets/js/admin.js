/* Dhaa Baja — Admin dashboard JS */

document.addEventListener('DOMContentLoaded', function () {
    // Confirm before any destructive action (delete forms use data-confirm="...")
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Show the selected filename + a live preview hint on the rhythm sheet-music upload
    var fileInput = document.getElementById('sheet_file');
    if (fileInput) {
        var label = document.getElementById('sheet_file_label');
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files.length > 0) {
                label.textContent = 'Selected: ' + fileInput.files[0].name;
            } else {
                label.textContent = 'No file selected';
            }
        });

        // Basic drag-and-drop styling on the dropzone wrapper
        var dropzone = fileInput.closest('.upload-dropzone');
        if (dropzone) {
            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.add('drag-over');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('drag-over');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }
    }

});
