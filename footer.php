    </main>
    <footer class="bg-white border-t border-gray-100 py-10 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="flex items-center">
                    <?php if (!empty($favicon_url)): ?>
                        <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="" class="w-6 h-6 mr-2">
                    <?php endif; ?>
                    <span class="text-primary font-semibold"><?php echo htmlspecialchars($site_title ?? 'Video Platform'); ?></span>
                </div>
                <div class="text-gray-500 text-sm">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_title ?? 'Video Platform'); ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
