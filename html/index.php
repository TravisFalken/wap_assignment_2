<?php
// This is the main page for the site.

// Include the configuration file:
require('includes/config.inc.php');
// Set the page title and include the HTML header:
$page_title = 'Welcome to this Site!';
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); //get the current page
include('includes/header.html');

include('includes/navigation_bar.html');

//Connect to db
require(MYSQL);


// Make the query to get to 5 latest products:
$q = "  SELECT 
            	pd.product_id
            ,   pd.product_title
            ,   pd.product_description
            ,   pd.price
            ,	(
                    SELECT
                        ig.image_name
                    FROM product_images pi
                    INNER JOIN images ig ON ig.image_id = pi.image_id
                    WHERE 	pi.product_id = pd.product_id
                    AND		pi.cover_image = 1	
                    LIMIT 1
                ) AS cover_image_name
            FROM        products pd
            ORDER BY created_date DESC
			LIMIT 5";

$r = mysqli_query($dbc, $q); //Query db

$totalNum = mysqli_num_rows($r);

// Welcome the user (by name if they are logged in):
echo '<h1>Welcome';
if (isset($_SESSION['first_name'])) {
	echo ", {$_SESSION['first_name']}";
}
echo '!</h1>
		<div class="container" style="margin-top:40px">
		<h2>LaptopsRUs world leading store!</h2>
		<p>Browse through our store to see what product best suits you.</p>
		<p><b>On Sale Now!</b></p>
		<a href="' . BASE_URL . 'store.php" class="btn btn-primary btn-lg">Shop Now!</a>';


if ($totalNum > 0) {
	echo '<hr style="margin-top: 40px"><h2>New Releases!!</h2><div class="slideshow-container">';

	$numberOfImages = 1;
	while ($row = $r->fetch_object()) {
		echo '	<div class="mySlides fade">
				<div class="numbertext">' . $numberOfImages . '/' . $totalNum . '</div>
				<div class="row d-flex justify-content-center"><img src="' . BASE_URL . 'uploads/' . $row->cover_image_name . '" style="height: 350px"></div>
				<div class="text"><a href="' . BASE_URL . 'view_product.php?id=' . $row->product_id . '" class="btn btn-primary">Buy Now!</a></div>
			</div>';
		$numberOfImages = $numberOfImages + 1;
	}

	$r->free(); // Free up the resources.
	unset($r);

	echo '
			<a class="prev" onclick="plusSlides(-1)">&#10094;</a>
			<a class="next" onclick="plusSlides(1)">&#10095;</a>
			</div>
			<br>
			<div style="text-align:center">';

	$numberOfImages = 0;
	while ($numberOfImages < $totalNum) {
		echo '<span class="dot" onclick="currentSlide(' . $numberOfImages . ')"></span>';
		$numberOfImages = $numberOfImages + 1;
	}
	echo		'</div>
			<script>
			var slideIndex = 1;
			showSlides(slideIndex);

			function plusSlides(n) {
			showSlides(slideIndex += n);
			}

			function currentSlide(n) {
			showSlides(slideIndex = n);
			}

			function showSlides(n) {
			var i;
			var slides = document.getElementsByClassName("mySlides");
			var dots = document.getElementsByClassName("dot");
			console.log(slides);
			if (n > slides.length) {slideIndex = 1}    
			if (n < 1) {slideIndex = slides.length}
			for (i = 0; i < slides.length; i++) {
				slides[i].style.display = "none";  
			}
			for (i = 0; i < dots.length; i++) {
				dots[i].className = dots[i].className.replace(" active", "");
			}
			slides[slideIndex-1].style.display = "block";  
			dots[slideIndex-1].className += " active";
			}
			</script>
			';
}

echo '<hr style="margin-top: 40px"><div class="row">
<div class="col-md-6 col-lg-3" style="margin-bottom: 12px; margin-top: 40px;">
	<div class="card" style="height: 250px; overflow:hidden;">
		<div class="card-body">
			<div class="card-text">
				<h4>Top Quality!</h4>
				<p>Products go through strict quality assessments.</p>
				<div class="row d-flex justify-content-center">
					<i class="fas fa-certificate" style="font-size: 50px"></i>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="col-md-6 col-lg-3" style="margin-bottom: 12px; margin-top: 40px;">
	<div class="card" style="height: 250px; overflow:hidden;">
		<div class="card-body">
			<div class="card-text">
				<h4>Fast Delivery!</h4>
				<p>We offer same day delivery of items!</p>
				<div class="row d-flex justify-content-center">
					<i class="fas fa-shipping-fast" style="font-size: 50px"></i>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="col-md-6 col-lg-3" style="margin-bottom: 12px; margin-top: 40px;">
<div class="card" style="height: 250px; overflow:hidden;">
	<div class="card-body">
		<div class="card-text">
			<h4>The Best Makes!</h4>
			<p>Top laptop brands from Apple to Lenovo!</p>
			<div class="row d-flex justify-content-center">
				<i class="fab fa-apple" style="font-size: 50px"></i>
			</div>
		</div>
	</div>
</div>
</div>
<div class="col-md-6 col-lg-3" style="margin-bottom: 12px; margin-top: 40px;">
<div class="card" style="height: 250px; overflow:hidden;">
<div class="card-body">
	<div class="card-text">
		<h4>Caring Staff!</h4>
		<p>At ComputersRUs our staff only want the best for you!</p>
		<div class="row d-flex justify-content-center">
			<i class="fas fa-users" style="font-size: 50px"></i>
		</div>
	</div>
</div>
</div>
</div>
</div>';

echo '</div>';
?>

<?php include('includes/footer.html'); ?>