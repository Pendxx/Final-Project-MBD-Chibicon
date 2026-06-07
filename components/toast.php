<?php
// toast.php - UI Helper for rendering success/error notifications
// Expects: $_SESSION['toast'] and $_SESSION['toast_type'] to be optionally set

$toast_msg = '';
$toast_type = 'success';

if (isset($_SESSION['toast'])) {
    $toast_msg = $_SESSION['toast'];
    $toast_type = $_SESSION['toast_type'] ?? 'success';
    
    // Clear it after grabbing
    unset($_SESSION['toast']);
    unset($_SESSION['toast_type']);
}
?>

<?php if ($toast_msg): ?>
<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 toast-enter">
    <div class="flex items-center p-4 w-full max-w-xs text-on-surface bg-surface-container-lowest rounded-lg shadow-lg border border-outline-variant" role="alert">
        <?php if ($toast_type === 'success'): ?>
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-on-primary-container bg-primary-container rounded-lg">
            <span class="material-symbols-outlined text-[20px]">check_circle</span>
        </div>
        <?php elseif ($toast_type === 'error'): ?>
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-on-error-container bg-error-container rounded-lg">
            <span class="material-symbols-outlined text-[20px]">error</span>
        </div>
        <?php endif; ?>
        
        <div class="ml-3 text-sm font-body-md"><?= htmlspecialchars($toast_msg) ?></div>
        
        <button type="button" onclick="closeToast()" class="ml-auto -mx-1.5 -my-1.5 bg-surface-container-lowest text-on-surface-variant hover:text-on-surface rounded-lg p-1.5 hover:bg-surface-container-low inline-flex items-center justify-center h-8 w-8 transition-colors">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
</div>

<script>
    // Animate toast entry
    document.addEventListener('DOMContentLoaded', () => {
        const toast = document.getElementById('toast-container');
        if (toast) {
            // Trigger enter animation
            setTimeout(() => {
                toast.classList.remove('toast-enter');
                toast.classList.add('toast-enter-active');
            }, 10);
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                closeToast();
            }, 3000);
        }
    });

    function closeToast() {
        const toast = document.getElementById('toast-container');
        if (toast) {
            toast.classList.remove('toast-enter-active');
            toast.classList.add('toast-exit-active');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    }
</script>
<?php endif; ?>
