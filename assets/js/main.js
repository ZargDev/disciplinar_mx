document.addEventListener("DOMContentLoaded", function() {
  const filas = document.querySelectorAll(".fila-alumno");
  const htmlEl = document.documentElement;
  const switchEl = document.getElementById("themeSwitch");

  console.log("Número de filas:", filas.length);
  filas.forEach(fila => {
    fila.style.cursor = "pointer";
    fila.addEventListener("click", () => {
      console.log("Clic en fila con id:", fila.getAttribute("data-id"));
      filas.forEach(f => {
        const acc = f.querySelector(".acciones");
        if (acc) acc.style.display = "none";
        f.classList.remove("table-active");
      });

      const acciones = fila.querySelector(".acciones");
      if (acciones) {
        acciones.style.display = "inline-block";
        console.log("Acciones encontradas y mostradas para fila:", fila.getAttribute("data-id"));
      } else {
        console.log("No encontré .acciones en esta fila");
      }
      fila.classList.add("table-active");
    });
  });
});
