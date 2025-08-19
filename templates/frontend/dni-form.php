        <div class="tb-container" <?php echo $container_style; ?>>
            <h3>Verificación de DNI</h3>
            <form method="post">
                <p class="tb-form-group">
                    <label for="tb_dni">Introduce tu DNI:</label>
                    <input type="text" id="tb_dni" name="tb_dni" required placeholder="Ej: 12345678A">
                </p>
                <p class="tb-form-group">
                    <label for="tb_email">Introduce tu correo electrónico:</label>
                    <input type="email" id="tb_email" name="tb_email" required placeholder="ejemplo@correo.com">
                </p>
                <p class="tb-form-actions">
                    <input type="submit" name="tb_submit_dni" value="Verificar Datos" class="tb-button">
                </p>
            </form>
        </div>
