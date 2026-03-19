function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('modalSaveBtn').textContent = 'Save Product';
    document.getElementById('productId').value = ''; 
    document.getElementById('productName').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '0';
    document.getElementById('productImage').value = '';
    document.getElementById('productDesc').value = '';
    document.getElementById('productDept').value = '';
    document.getElementById('productStockGroup').style.display = '';
    document.getElementById('variantsSection').style.display = 'block';
    document.getElementById('variantsTableBody').innerHTML = '<tr><td colspan="4" class="text-muted small">Add variants below, or leave empty to use Stock for one default variant.</td></tr>';
    var toAdd = document.getElementById('variantsToAddBody');
    if (toAdd) toAdd.innerHTML = '';
    populateCategories('');
    document.getElementById('productModal').classList.add('show');
}

function openEditModal(id, name, price, dept, category, stock, desc, img) {
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('modalSaveBtn').textContent = 'Update Product';
    document.getElementById('productId').value = id; 
    document.getElementById('productName').value = name;
    document.getElementById('productPrice').value = price;
    document.getElementById('productStock').value = stock;
    document.getElementById('productDesc').value = desc;
    document.getElementById('productDept').value = dept;
    const fileInput = document.getElementById('productImage');
    if (fileInput) fileInput.value = '';
    document.getElementById('productStockGroup').style.display = 'none';
    document.getElementById('variantsSection').style.display = 'block';
    document.getElementById('variantsToAddBody').innerHTML = '';
    populateCategories(dept, category);
    document.getElementById('productModal').classList.add('show');
    loadVariants(id);
}

function loadVariants(productId) {
    fetch('get_variants.php?product_id=' + productId)
        .then(r => r.json())
        .then(data => {
            if (data.error) return;
            renderVariants(data.variants || []);
        })
        .catch(() => renderVariants([]));
}

function renderVariants(variants) {
    const tbody = document.getElementById('variantsTableBody');
    if (!tbody) return;
    tbody.innerHTML = variants.map(v => `
        <tr data-variant-id="${v.variant_id}">
            <td><input type="text" class="form-control form-control-sm variant-size" value="${escapeHtml(v.size || '')}" list="sizeList" style="width:90px" placeholder="Size"></td>
            <td><input type="text" class="form-control form-control-sm variant-colour" value="${escapeHtml(v.colour || '')}" list="colourList" style="width:90px" placeholder="Colour"></td>
            <td><input type="number" class="form-control form-control-sm variant-stock" value="${v.stock_quantity}" min="0" style="width:70px"></td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-dark update-variant-btn">Update</button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-variant-btn">Delete</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="4" class="text-muted">No variants yet. Add one above.</td></tr>';

    tbody.querySelectorAll('.update-variant-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const variantId = row.dataset.variantId;
            const size = row.querySelector('.variant-size').value.trim() || null;
            const colour = row.querySelector('.variant-colour').value.trim() || null;
            const stock = parseInt(row.querySelector('.variant-stock').value, 10) || 0;
            updateVariant(variantId, size, colour, stock, row);
        });
    });
    tbody.querySelectorAll('.delete-variant-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const variantId = row.dataset.variantId;
            if (confirm('Delete this variant?')) deleteVariant(variantId, row);
        });
    });
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function updateVariant(variantId, size, colour, stock, row) {
    const fd = new FormData();
    fd.append('action', 'update_variant');
    fd.append('variant_id', variantId);
    fd.append('size', size || '');
    fd.append('colour', colour || '');
    fd.append('stock', stock);
    fetch('variant_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                row.classList.add('table-success');
                setTimeout(() => row.classList.remove('table-success'), 800);
            } else alert(data.error || 'Update failed');
        })
        .catch(() => alert('Update failed'));
}

function deleteVariant(variantId, row) {
    const productId = document.getElementById('productId').value;
    const fd = new FormData();
    fd.append('action', 'delete_variant');
    fd.append('variant_id', variantId);
    fetch('variant_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadVariants(productId);
            else alert(data.error || 'Delete failed');
        })
        .catch(() => alert('Delete failed'));
}

document.addEventListener('DOMContentLoaded', function() {
    const sizeSelect = document.getElementById('newVariantSize');
    const sizeCustom = document.getElementById('newVariantSizeCustom');
    const colourSelect = document.getElementById('newVariantColour');
    const colourCustom = document.getElementById('newVariantColourCustom');

    if (sizeSelect && sizeCustom) {
        sizeSelect.addEventListener('change', function() {
            sizeCustom.style.display = this.value === '_custom' ? 'block' : 'none';
            sizeCustom.value = '';
        });
    }
    if (colourSelect && colourCustom) {
        colourSelect.addEventListener('change', function() {
            colourCustom.style.display = this.value === '_custom' ? 'block' : 'none';
            colourCustom.value = '';
        });
    }

    const addVariantBtn = document.getElementById('addVariantBtn');
    if (addVariantBtn) {
        addVariantBtn.addEventListener('click', function() {
            let size = sizeSelect?.value === '_custom' ? (sizeCustom?.value || '').trim() : (sizeSelect?.value || '').trim();
            let colour = colourSelect?.value === '_custom' ? (colourCustom?.value || '').trim() : (colourSelect?.value || '').trim();
            const stock = parseInt(document.getElementById('newVariantStock').value, 10) || 0;

            const productId = document.getElementById('productId').value;
            if (productId) {
                size = size || null;
                colour = colour || null;
                const fd = new FormData();
                fd.append('action', 'add_variant');
                fd.append('product_id', productId);
                fd.append('size', size || '');
                fd.append('colour', colour || '');
                fd.append('stock', stock);
                fetch('variant_process.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (sizeSelect) sizeSelect.value = '';
                            if (sizeCustom) { sizeCustom.value = ''; sizeCustom.style.display = 'none'; }
                            if (colourSelect) colourSelect.value = '';
                            if (colourCustom) { colourCustom.value = ''; colourCustom.style.display = 'none'; }
                            document.getElementById('newVariantStock').value = '0';
                            loadVariants(productId);
                        } else alert(data.error || 'Add failed');
                    })
                    .catch(() => alert('Add failed'));
            } else {
                var toAddBody = document.getElementById('variantsToAddBody');
                if (!toAddBody) return;
                var sizeVal = size || 'One Size';
                var colourVal = colour || '';
                var row = document.createElement('tr');
                row.innerHTML = '<td>' + escapeHtml(sizeVal) + '</td><td>' + escapeHtml(colourVal) + '</td><td>' + stock + '</td><td><button type="button" class="btn btn-sm btn-outline-danger remove-pending-variant">Remove</button>' +
                    '<input type="hidden" name="variant_size[]" value="' + escapeHtml(sizeVal) + '">' +
                    '<input type="hidden" name="variant_colour[]" value="' + escapeHtml(colourVal) + '">' +
                    '<input type="hidden" name="variant_stock[]" value="' + stock + '"></td>';
                toAddBody.appendChild(row);
                row.querySelector('.remove-pending-variant')?.addEventListener('click', function() { row.remove(); });
                if (sizeSelect) sizeSelect.value = '';
                if (sizeCustom) { sizeCustom.value = ''; sizeCustom.style.display = 'none'; }
                if (colourSelect) colourSelect.value = '';
                if (colourCustom) { colourCustom.value = ''; colourCustom.style.display = 'none'; }
                document.getElementById('newVariantStock').value = '0';
            }
        });
    }
});

function openDeleteModal(id, name) {
    document.getElementById('deleteProductName').textContent = '"' + name + '"';
    document.getElementById('deleteProductId').value = id;
    document.getElementById('deleteModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('searchInput');
    const deptFilter = document.getElementById('deptFilter');
    const catFilter = document.getElementById('catFilter');
    const tableRows = document.querySelectorAll('.table-wrap tbody tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const deptValue = deptFilter.value.toLowerCase();
        const catValue = catFilter.value.toLowerCase();

        tableRows.forEach(row => {
            const productName = row.cells[0].textContent.toLowerCase();
            const department = row.cells[1].textContent.trim().toLowerCase();
            const category = row.cells[2].textContent.trim().toLowerCase();

            const matchesSearch = productName.includes(searchTerm);
            const matchesDept = deptValue === "" || department === deptValue;
            const matchesCat = catValue === "" || category === catValue;

            if (matchesSearch && matchesDept && matchesCat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if(searchInput) searchInput.addEventListener('input', filterTable);
    if(deptFilter) deptFilter.addEventListener('change', filterTable);
    if(catFilter) catFilter.addEventListener('change', filterTable);
});


const categoryMap = window.categoryMap || {
    'Men': ['T-Shirts', 'Hoodies','Jackets','Pants','Accessories'],
    'Women': ['Dresses', 'Tops', 'Jackets','Pants','Accessories'],
};

const deptSelect = document.getElementById('productDept');
const catSelect = document.getElementById('productCategory');

if (deptSelect) {
    deptSelect.addEventListener('change', function() {
        populateCategories(this.value);
    });
}

function populateCategories(department, selectedCategory = '') {

    catSelect.innerHTML = '<option value="">Select category</option>';

    if (categoryMap[department]) {
        catSelect.disabled = false;
        
        categoryMap[department].forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;

            if (cat === selectedCategory) {
                option.selected = true;
            }
            catSelect.appendChild(option);
        });
    } else {
        catSelect.disabled = true;
        catSelect.innerHTML = '<option value="">Select department first</option>';
    }
}

const filterDeptSelect = document.getElementById('deptFilter');
const filterCatSelect = document.getElementById('catFilter');

if (filterDeptSelect && filterCatSelect) {
    filterDeptSelect.addEventListener('change', function() {
        const selectedDept = this.value;
        
        filterCatSelect.innerHTML = '<option value="">All Categories</option>';
        
        let categoriesToLoad = [];
        
        if (selectedDept !== "" && categoryMap[selectedDept]) {
            categoriesToLoad = categoryMap[selectedDept];
        } else {
            categoriesToLoad = Object.values(categoryMap).flat();
        }
        
        categoriesToLoad.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;
            filterCatSelect.appendChild(option);
        });
        
        filterCatSelect.value = ""; 
    });
}