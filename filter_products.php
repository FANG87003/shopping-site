<?php
include "config.php";
$db_link->query("SET NAMES 'utf8'");

$data = json_decode(file_get_contents('php://input'), true);
$categories = $data['categories'] ?? [];

if (!empty($categories)) {
    $categories_str = implode(',', array_map('intval', $categories));
    $query = "
        SELECT * 
        FROM products 
        WHERE EXISTS (
            SELECT 1 FROM categories c 
            WHERE FIND_IN_SET(c.id, products.category_ids) > 0 
            AND c.id IN ($categories_str)
        )
    ";
} else {
    $query = "SELECT * FROM products LIMIT 12";
}

$result = $db_link->query($query);

if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()): ?>
        <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-6">
            <div class="single-product mb-15 wow fadeInUp" data-wow-delay=".1s">
                <div class="product-thumb">
                    <img src="path/to/images/<?= htmlspecialchars($product['id']) ?>.jpg" alt="Product Image">
                    <div class="cart-btn cart-btn-1 p-abs">
                        <a href="#">Add to cart</a>
                    </div>
                </div>
                <div class="product-content">
                    <h4 class="pro-title pro-title-1"><a href="product-details.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h4>
                    <div class="pro-price">
                        <span>NT <?= htmlspecialchars($product['price_new']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile;
} else {
    echo "<p>No products found for the selected categories.</p>";
}
?>
