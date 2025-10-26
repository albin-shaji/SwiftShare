document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const downloadForm = document.getElementById('downloadForm');
    const fileToUpload = document.getElementById('fileToUpload');
    const shareCodeInput = document.getElementById('shareCode');

    // Define common dark mode SweetAlert2 styles for a professional look
    const darkSwalConfig = {
        background: '#1a202c', // Dark background, matching a common dark theme
        color: '#ffffff',       // White text color for readability
        confirmButtonColor: '#6366f1', // Primary action button color (indigo)
        // Custom classes for more fine-grained control and rounded edges
        customClass: {
            popup: 'rounded-xl shadow-2xl', // Apply rounded corners and deeper shadow
            title: 'text-2xl font-poppins font-bold', // Larger, bolder title
            htmlContainer: 'text-base', // Standard text size
            confirmButton: 'px-6 py-3 text-lg font-semibold rounded-lg' // Larger, rounded button
        },
        // Ensure consistent padding and spacing
        padding: '2em',
        width: 'auto', // Allow width to adjust based on content and screen size
        // Animations for a smoother feel
        showClass: {
            popup: 'animate__animated animate__fadeInDown animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp animate__faster'
        }
    };

    // New element for displaying the chosen file name
    const fileNameDisplay = document.getElementById('fileNameDisplay');

    // Add event listener for the file input change to update the displayed file name
    if (fileToUpload && fileNameDisplay) {
        fileToUpload.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = 'No file chosen';
            }
        });
    }

    // Handle Upload Form Submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!fileToUpload.files.length) {
                Swal.fire({
                    ...darkSwalConfig,
                    icon: 'warning',
                    title: 'No file selected',
                    text: 'Please choose a file to upload.',
                });
                return;
            }

            const formData = new FormData();
            formData.append('fileToUpload', fileToUpload.files[0]);

            try {
                Swal.fire({
                    ...darkSwalConfig,
                    title: 'Uploading...',
                    text: 'Please wait while your file is being uploaded.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        // Optional: Add a progress bar or more detailed upload status here
                    }
                });

                const response = await fetch('/swiftshare/php/upload.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    const uniqueCode = result.data.unique_code;
                    Swal.fire({
                        ...darkSwalConfig,
                        icon: 'success',
                        title: 'Upload Successful!',
                        html: `
                            <p class="text-gray-300 mb-4">Your file is ready! Share this code:</p>
                            <div class="bg-gray-800 p-4 rounded-lg flex items-center justify-between mb-6">
                                <strong id="shareCodeDisplay" class="text-3xl text-indigo-400 font-bold tracking-widest select-all">${uniqueCode}</strong>
                                <button id="copyCodeBtn"
                                    class="ml-4 bg-indigo-700 hover:bg-indigo-800 text-white px-4 py-2 rounded-lg transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-75 flex items-center gap-2">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                            <p class="text-sm text-gray-400">It will expire in 5 minutes or 3 downloads.</p>
                        `,
                        confirmButtonText: 'Got It!', // Custom text for the confirm button
                        didOpen: (modal) => {
                            // Attach event listener to the copy button once the modal is open
                            const copyButton = modal.querySelector('#copyCodeBtn');
                            const shareCodeDisplay = modal.querySelector('#shareCodeDisplay');

                            if (copyButton && shareCodeDisplay) {
                                copyButton.addEventListener('click', () => {
                                    // Use a temporary textarea for copying to clipboard
                                    const tempInput = document.createElement('textarea');
                                    tempInput.value = shareCodeDisplay.textContent;
                                    document.body.appendChild(tempInput);
                                    tempInput.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(tempInput);

                                    // Provide visual feedback
                                    copyButton.textContent = 'Copied!';
                                    copyButton.classList.remove('bg-indigo-700', 'hover:bg-indigo-800');
                                    copyButton.classList.add('bg-green-600');
                                    setTimeout(() => {
                                        copyButton.textContent = 'Copy';
                                        copyButton.classList.remove('bg-green-600');
                                        copyButton.classList.add('bg-indigo-700', 'hover:bg-indigo-800');
                                    }, 1500); // Revert button text after 1.5 seconds
                                });
                            }
                        }
                    });
                    uploadForm.reset(); // Clear the file input
                    fileNameDisplay.textContent = 'No file chosen'; // Reset the file name display
                } else {
                    Swal.fire({
                        ...darkSwalConfig,
                        icon: 'error',
                        title: 'Upload Failed',
                        text: result.message,
                        confirmButtonColor: '#ef4444' // Error button color
                    });
                }
            } catch (error) {
                console.error('Upload error:', error);
                Swal.fire({
                    ...darkSwalConfig,
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not connect to the server. Please try again.',
                    confirmButtonColor: '#ef4444' // Error button color
                });
            }
        });
    }

    // Handle Download Form Submission
    if (downloadForm) {
        downloadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const shareCode = shareCodeInput.value.trim().toUpperCase(); // Ensure uppercase for consistency

            if (shareCode.length !== 6 || !/^[A-Z0-9]{6}$/.test(shareCode)) {
                Swal.fire({
                    ...darkSwalConfig,
                    icon: 'warning',
                    title: 'Invalid Code',
                    text: 'Please enter a valid 6-digit alphanumeric code.',
                });
                return;
            }

            // Show a loading indicator while we validate the code
            Swal.fire({
                ...darkSwalConfig,
                title: 'Verifying code...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const startTime = Date.now();
            const minLoadingDisplayTime = 1500; // 1.5 seconds in milliseconds

            try {
                const validationResponse = await fetch(`/swiftshare/php/download.php?code=${shareCode}&action=validate`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await validationResponse.json();

                const elapsedTime = Date.now() - startTime;
                const remainingTime = minLoadingDisplayTime - elapsedTime;
                if (remainingTime > 0) {
                    await new Promise(resolve => setTimeout(resolve, remainingTime));
                }

                if (result.status === 'success') {
                    Swal.close();

                    Swal.fire({
                        ...darkSwalConfig,
                        icon: 'success',
                        title: 'Download Initiated!',
                        text: 'Your file should be downloading now. Check your downloads folder.',
                        timer: 1200, // Show this message for 1.2 seconds
                        timerProgressBar: true,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        willClose: () => {
                             window.location.href = `/swiftshare/php/download.php?code=${shareCode}`;
                        }
                    }).then((result) => {
                        // This .then() block handles what happens after the success modal closes.
                    });

                } else {
                    Swal.close();
                    Swal.fire({
                        ...darkSwalConfig,
                        icon: 'error',
                        title: 'Download Failed',
                        text: result.message,
                        confirmButtonColor: '#ef4444' // Error button color
                    });
                }
            } catch (error) {
                console.error('Download validation error:', error);
                Swal.close();
                Swal.fire({
                    ...darkSwalConfig,
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not connect to the server. Please try again.',
                    confirmButtonColor: '#ef4444' // Error button color
                });
            }
        });
    }

    // Optional: Add some client-side validation for the share code input
    document.getElementById('shareCode')?.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); // Only allow alphanumeric and convert to uppercase
    });
});  
