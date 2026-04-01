function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('modalSaveBtn').textContent = 'Save Product';
    document.getElementById('productId').value = ''; 
    document.getElementById('productName').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '0';
    var imgInput = document.getElementById('productImage');
    if (imgInput) { imgInput.value = ''; }
    var addFileName = document.getElementById('addFileName');
    if (addFileName) addFileName.textContent = 'No file chosen';
    document.getElementById('productDesc').value = '';
    document.getElementById('productDept').value = '';
    document.getElementById('productStockGroup').style.display = '';
    document.getElementById('variantsSection').style.display = 'block';
    document.getElementById('imageAddState').style.display = '';
    document.getElementById('imageEditState').style.display = 'none';
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
    document.getElementById('imageAddState').style.display = 'none';
    document.getElementById('imageEditState').style.display = '';
    var preview = document.getElementById('productImagePreview');
    var replaceName = document.getElementById('replaceFileName');
    if (preview) {
        var imgSrc = img ? (img.indexOf('http') === 0 || img.indexOf('../') === 0 ? img : '../' + img) : '';
        preview.src = imgSrc;
        preview.alt = name || 'Current product';
    }
    if (replaceName) replaceName.textContent = 'No file chosen';
    document.getElementById('variantsToAddBody').innerHTML = '';
    populateCategories(dept, category);
    document.getElementById('productModal').classList.add('show');
    loadVariants(id);
}

const SIZE_OPTIONS = ['', 'XS', 'S', 'M', 'L', 'XL', '2XL', 'One Size'];
const COLOUR_OPTIONS = ['', 'Black', 'White', 'Navy', 'Grey', 'Charcoal', 'Red', 'Blue', 'Green', 'Burgundy', 'Camel', 'Brown', 'Pink', 'Beige'];

function makeSizeOptions(selected) {
    var opts = SIZE_OPTIONS.filter(s => s !== '').map(s =>
        '<option value="' + escapeHtml(s) + '"' + (s === selected ? ' selected' : '') + '>' + escapeHtml(s) + '</option>'
    ).join('');
    if (selected && !SIZE_OPTIONS.includes(selected)) {
        opts = '<option value="' + escapeHtml(selected) + '" selected>' + escapeHtml(selected) + '</option>' + opts;
    }
    return '<option value="">—</option>' + opts;
}

function makeColourOptions(selected) {
    var opts = COLOUR_OPTIONS.filter(c => c !== '').map(c =>
        '<option value="' + escapeHtml(c) + '"' + (c === selected ? ' selected' : '') + '>' + escapeHtml(c) + '</option>'
    ).join('');
    if (selected && !COLOUR_OPTIONS.includes(selected)) {
        opts = '<option value="' + escapeHtml(selected) + '" selected>' + escapeHtml(selected) + '</option>' + opts;
    }
    return '<option value="">—</option>' + opts;
}

function loadVariants(productId) {
    fetch(window.location.pathname.replace(/\/[^/]*$/, '/') + 'get_variants.php?product_id=' + productId)
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
    var sizeVal, colourVal;
    tbody.innerHTML = variants.map(v => {
        sizeVal = v.size || '';
        colourVal = v.colour || '';
        return '<tr data-variant-id="' + v.variant_id + '">' +
            '<td><select class="form-select form-select-sm variant-size" style="width:100px">' + makeSizeOptions(sizeVal) + '</select></td>' +
            '<td><select class="form-select form-select-sm variant-colour" style="width:100px">' + makeColourOptions(colourVal) + '</select></td>' +
            '<td><input type="number" class="form-control form-control-sm variant-stock" value="' + v.stock_quantity + '" min="0" style="width:70px"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-dark update-variant-btn">Update</button> ' +
            '<button type="button" class="btn btn-sm btn-outline-danger delete-variant-btn">Delete</button></td></tr>';
    }).join('') || '<tr><td colspan="4" class="text-muted">No variants yet. Add one above.</td></tr>';

    tbody.querySelectorAll('.update-variant-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const row = this.closest('tr');
            const variantId = row.dataset.variantId;
            const size = row.querySelector('.variant-size').value.trim() || null;
            const colour = row.querySelector('.variant-colour').value.trim() || null;
            const stock = parseInt(row.querySelector('.variant-stock').value, 10) || 0;
            updateVariant(variantId, size, colour, stock, row);
        });
    });
    tbody.querySelectorAll('.delete-variant-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
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
    fetch(window.location.pathname.replace(/\/[^/]*$/, '/') + 'variant_process.php', { method: 'POST', body: fd })
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
    fetch(window.location.pathname.replace(/\/[^/]*$/, '/') + 'variant_process.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadVariants(productId);
            else alert(data.error || 'Delete failed');
        })
        .catch(() => alert('Delete failed'));
}

document.addEventListener('DOMContentLoaded', function() {
    var productImageInput = document.getElementById('productImage');
    var addImageBtn = document.getElementById('addImageBtn');
    var replaceImageBtn = document.getElementById('replaceImageBtn');
    var addFileName = document.getElementById('addFileName');
    var replaceFileName = document.getElementById('replaceFileName');

    if (addImageBtn && productImageInput) {
        addImageBtn.addEventListener('click', function() { productImageInput.click(); });
    }
    if (replaceImageBtn && productImageInput) {
        replaceImageBtn.addEventListener('click', function() { productImageInput.click(); });
    }
    var objectUrlRevoke = null;
    if (productImageInput) {
        productImageInput.addEventListener('change', function() {
            var name = this.files && this.files.length ? this.files[0].name : 'No file chosen';
            if (addFileName) addFileName.textContent = name;
            if (replaceFileName) replaceFileName.textContent = name;
            var preview = document.getElementById('productImagePreview');
            if (preview && this.files && this.files.length) {
                if (objectUrlRevoke) URL.revokeObjectURL(objectUrlRevoke);
                objectUrlRevoke = URL.createObjectURL(this.files[0]);
                preview.src = objectUrlRevoke;
            }
        });
    }

    var previewImg = document.getElementById('productImagePreview');
    var expandOverlay = document.getElementById('imageExpandOverlay');
    var expandImg = document.getElementById('imageExpandImg');
    function openImageExpand() {
        if (!previewImg || !previewImg.src) return;
        expandImg.src = previewImg.src;
        expandImg.alt = previewImg.alt || 'Product image';
        expandOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        expandOverlay.focus();
    }
    function closeImageExpand() {
        expandOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    if (previewImg && expandOverlay && expandImg) {
        previewImg.addEventListener('click', openImageExpand);
        previewImg.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openImageExpand(); } });
        expandOverlay.addEventListener('click', closeImageExpand);
        expandImg.addEventListener('click', function(e) { e.stopPropagation(); });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && expandOverlay.classList.contains('show')) closeImageExpand();
        });
    }

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
                fetch(window.location.pathname.replace(/\/[^/]*$/, '/') + 'variant_process.php', { method: 'POST', body: fd })
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
                row.innerHTML = '<td><select name="variant_size[]" class="form-select form-select-sm" style="width:100px">' + makeSizeOptions(sizeVal) + '</select></td>' +
                    '<td><select name="variant_colour[]" class="form-select form-select-sm" style="width:100px">' + makeColourOptions(colourVal) + '</select></td>' +
                    '<td><input type="number" name="variant_stock[]" class="form-control form-control-sm" value="' + stock + '" min="0" style="width:70px"></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger remove-pending-variant">Remove</button></td>';
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
            categoriesToLoad = [...new Set(Object.values(categoryMap).flat())];
        }
        
        categoriesToLoad.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;
            filterCatSelect.appendChild(option);
        });
        
        filterCatSelect.value = ""; 
    });

    filterDeptSelect.dispatchEvent(new Event('change'));
}