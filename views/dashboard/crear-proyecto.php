<?php include_once __DIR__ . '/../dashboard/header-dashboard.php';?>

    <div class="contenedor-sm">
        <?php include_once __DIR__ . '/../templates/alertas.php';?>
        <form action="/crear-proyecto" class="formulario" method="POST">
            <?php include_once __DIR__ . '/formulario-proyecto.php' ?>
            <input 
                type="submit"
                value="Crear Proyecto"
            />
        </form>
    </div>

<?php include_once __DIR__ . '/../dashboard/footer-dashboard.php';?>
