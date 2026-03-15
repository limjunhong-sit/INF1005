<footer class="text-center bg-light py-3 mt-auto">
    <p class="mb-0">Copyright &copy; 2026 UniClothes Pte. Ltd.</p>
</footer>

<script>
function setColour(swatch) {
    const overlayId = swatch.dataset.overlay || 'productOverlay';
    document.getElementById(overlayId).style.backgroundColor = swatch.dataset.color;

    document.getElementById('colourName').textContent = swatch.dataset.name;

    swatch.closest('.swatches').querySelectorAll('.swatch')
          .forEach(s => s.classList.remove('active'));
    swatch.classList.add('active');
}
</script>
