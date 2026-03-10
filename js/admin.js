function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('modalSaveBtn').textContent = 'Save Product';
    document.getElementById('productName').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '';
    document.getElementById('productImage').value = '';
    document.getElementById('productDesc').value = '';
    document.getElementById('productDept').value = '';
    document.getElementById('productCategory').value = '';
    document.getElementById('productModal').classList.add('show');
}

function openEditModal(name, price, dept, category, stock, desc) {
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('modalSaveBtn').textContent = 'Update Product';
    document.getElementById('productName').value = name;
    document.getElementById('productPrice').value = price;
    document.getElementById('productStock').value = stock;
    document.getElementById('productDesc').value = desc;
    document.getElementById('productDept').value = dept;
    document.getElementById('productCategory').value = category;
    document.getElementById('productImage').value = '';
    document.getElementById('productModal').classList.add('show');
}

function openDeleteModal(name) {
    document.getElementById('deleteProductName').textContent = '"' + name + '"';
    document.getElementById('deleteModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) closeModal(this.id);
    });
});