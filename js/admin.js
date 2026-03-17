function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('modalSaveBtn').textContent = 'Save Product';
    document.getElementById('productId').value = ''; 
    document.getElementById('productName').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '';
    document.getElementById('productImage').value = '';
    document.getElementById('productDesc').value = '';
    document.getElementById('productDept').value = '';
    // hide category dropdown until a department is chosen
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
    if (fileInput) {
        fileInput.value = '';
    }
    populateCategories(dept, category);
    document.getElementById('productModal').classList.add('show');
}

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