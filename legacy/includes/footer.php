    </main>
    
    <?php if (isLoggedIn()): ?>
    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><?php echo COMPANY_NAME; ?></h5>
                    <p class="text-muted small">
                        Professional Quotation & Invoice Management System
                    </p>
                </div>
                <div class="col-md-4">
                    <h6>Contact Information</h6>
                    <p class="small mb-1">
                        <i class="bi bi-envelope"></i> <?php echo COMPANY_EMAIL; ?>
                    </p>
                    <p class="small mb-1">
                        <i class="bi bi-telephone"></i> <?php echo COMPANY_PHONE; ?>
                    </p>
                    <p class="small mb-0">
                        <i class="bi bi-geo-alt"></i> <?php echo COMPANY_ADDRESS; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <p class="small text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>
                    </p>
                    <p class="small text-muted">
                        All Rights Reserved
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
</body>
</html>

