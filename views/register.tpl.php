<?php require_once __DIR__ . '/incs/header.tpl.php'; ?>

<div class="container mt-5">
    <div class="row">

        <div class="col-md-6 offset-md-3">

            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['errors'];
                    unset($_SESSION['errors']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post">

                <div class="form-floating mb-3">
                    <input type="text" name="name" class="form-control" id="name" placeholder="Name" value="<?= old('name') ?>">
                    <label for="name">Name</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" value="<?= old('email') ?>">
                    <label for="email">Email</label>
                </div>

                <div class="form-floating">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Password">
                    <label for="password">Password</label>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Register</button>

            </form>

        </div>

    </div>


</div>

<?php require_once __DIR__ . '/incs/footer.tpl.php'; ?>

