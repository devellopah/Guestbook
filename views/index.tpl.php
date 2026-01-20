<?php require_once __DIR__ . '/incs/header.tpl.php'; ?>

<div class="container mt-5">
    <div class="row">

        <div class="col-12 mb-4">

            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['errors'];
                    unset($_SESSION['errors']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

        </div>

        <form action="" class="mb-2">

            <div class="form-floating">
                <textarea class="form-control" placeholder="Leave a comment here" id="send-message"
                          style="height: 100px"></textarea>
                <label for="send-message">Comments</label>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Send</button>

        </form>

        <div class="col-12">
            <hr>
        </div>

    </div>

    <div class="row">

        <div class="col-12">

            <div class="card mb-3">
                <div class="card-body">

                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">User 1</h5>
                        <p class="message-created">2023-07-23 12:10</p>
                    </div>

                    <div class="card-text">
                        Some quick example text to build on the card title and make up the bulk of the card's content.
                    </div>

                    <div class="card-actions mt-2">
                        <p>
                            <a href="#">Disable</a> |
                            <a href="#">Approve</a> |
                            <a data-bs-toggle="collapse" href="#collapse-1">Edit</a>
                        </p>

                        <div class="collapse" id="collapse-1">
                            <form action="">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a comment here" id="message-1"
                                              style="height: 100px">Some placeholder content for the collapse component. This panel is hidden by default but revealed when the user activates the relevant trigger.</textarea>
                                    <label for="message-1">Comments</label>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Save</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">

                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">User 1</h5>
                        <p class="message-created">2023-07-20 12:10</p>
                    </div>

                    <div class="card-text">
                        Some quick example text to build on the card title and make up the bulk of the card's content 2...
                    </div>

                    <div class="card-actions mt-2">
                        <p>
                            <a href="#">Disable</a> |
                            <a href="#">Approve</a> |
                            <a data-bs-toggle="collapse" href="#collapse-2">Edit</a>
                        </p>

                        <div class="collapse" id="collapse-2">
                            <form action="">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a comment here" id="message-2"
                                              style="height: 100px">Some placeholder content for the collapse component. This panel is hidden by default but revealed when the user activates the relevant trigger.</textarea>
                                    <label for="message-2">Comments</label>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Save</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <div class="row">
        <div class="col-12">

            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/incs/footer.tpl.php'; ?>

