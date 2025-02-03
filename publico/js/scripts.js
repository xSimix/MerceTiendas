function abrirModal(id) {
    document.getElementById(id).style.display = "block";
}

function cerrarModal(id) {
    document.getElementById(id).style.display = "none";
}

function calcularEfectivoCaja() {
    const billetes = [200, 100, 50, 20, 10];
    const monedas = [5, 2, 1, 0.5];
    let total = 0;

    billetes.forEach((valor) => {
        const cantidad = document.getElementById(`billete_${valor}`).value || 0;
        total += valor * cantidad;
    });

    monedas.forEach((valor) => {
        const cantidad = document.getElementById(`moneda_${valor}`).value || 0;
        total += valor * cantidad;
    });

    document.getElementById("efectivo_caja").value = total.toFixed(2);
}
