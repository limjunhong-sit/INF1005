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
    // Lock the category dropdown until a department is chosen
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
    // For file inputs we cannot pre-fill the value for security reasons.
    // Leave the file field empty so the existing image is kept unless a new file is chosen.
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

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) closeModal(this.id);
    });
});

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

// 1. Define which categories belong to which department
// Prefer server-provided map if available, fallback to defaults
const categoryMap = window.categoryMap || {
    'Men': ['T-Shirts', 'Hoodies'],
    'Women': ['Dresses', 'Tops']
};

const deptSelect = document.getElementById('productDept');
const catSelect = document.getElementById('productCategory');

// 2. Listen for changes on the Department dropdown
if (deptSelect) {
    deptSelect.addEventListener('change', function() {
        populateCategories(this.value);
    });
}

// 3. The function that rewrites the Category dropdown
function populateCategories(department, selectedCategory = '') {
    // Clear out whatever is currently in the Category dropdown
    catSelect.innerHTML = '<option value="">Select category</option>';

    // If a valid department was chosen, unlock the Category dropdown and fill it
    if (categoryMap[department]) {
        catSelect.disabled = false; // Unlock it
        
        // Loop through the array (e.g., ['T-Shirts', 'Hoodies']) and create <option> tags
        categoryMap[department].forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;
            
            // If we are editing, pre-select the correct category
            if (cat === selectedCategory) {
                option.selected = true;
            }
            catSelect.appendChild(option);
        });
    } else {
        // If they chose "Select department", lock it again
        catSelect.disabled = true;
        catSelect.innerHTML = '<option value="">Select department first</option>';
    }
}

// ==========================================
// CASCADING DROPDOWN FOR TABLE FILTERS
// ==========================================
const filterDeptSelect = document.getElementById('deptFilter');
const filterCatSelect = document.getElementById('catFilter');

if (filterDeptSelect && filterCatSelect) {
    filterDeptSelect.addEventListener('change', function() {
        const selectedDept = this.value;
        
        // 1. Clear it out and always start with the default "All Categories" option
        filterCatSelect.innerHTML = '<option value="">All Categories</option>';
        
        let categoriesToLoad = [];
        
        // 2. Decide which categories to show
        if (selectedDept !== "" && categoryMap[selectedDept]) {
            // If they chose a specific department (like "Men"), only load those
            categoriesToLoad = categoryMap[selectedDept];
        } else {
            // If they chose "All Departments", combine all categories into one big list
            // .flat() takes the Men and Women arrays and merges them together
            categoriesToLoad = Object.values(categoryMap).flat();
        }
        
        // 3. Build the actual <option> tags and add them to the dropdown
        categoriesToLoad.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;
            filterCatSelect.appendChild(option);
        });
        
        // 4. Reset the category selection back to "All" to prevent filtering bugs
        filterCatSelect.value = ""; 
    });
}