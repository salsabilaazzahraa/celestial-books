document.addEventListener('DOMContentLoaded', function () {
    // Toggle sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // Print function
    function printBuktiPeminjaman() {
        window.print();
    }

    // Initialize print button in loan receipt page
    const printBtn = document.getElementById('print-bukti');
    if (printBtn) {
        printBtn.addEventListener('click', printBuktiPeminjaman);
    }

    // Toggle submenu visibility
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const submenu = this.querySelector('.submenu');
            if (submenu) {
                const isOpen = submenu.style.display === 'block';
                submenu.style.display = isOpen ? 'none' : 'block';
            }
        });
    });
});