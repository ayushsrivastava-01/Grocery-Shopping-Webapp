<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
   header('location:login.php');
};

if (isset($_POST['order'])) {
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
   $method = filter_var($_POST['method'], FILTER_SANITIZE_STRING);
   $address = 'flat no. ' . $_POST['flat'] . ' ' . $_POST['street'] . ' ' . $_POST['city'] . ' ' . $_POST['state'] . ' ' . $_POST['country'] . ' - ' . $_POST['pin_code'];
   $address = filter_var($address, FILTER_SANITIZE_STRING);
   $placed_on = date('d-M-Y');

   $cart_total = 0;
   $cart_products = [];

   $cart_query = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $cart_query->execute([$user_id]);
   if ($cart_query->rowCount() > 0) {
      while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
         $cart_products[] = $cart_item['name'] . ' ( ' . $cart_item['quantity'] . ' )';
         $sub_total = ($cart_item['price'] * $cart_item['quantity']);
         $cart_total += $sub_total;
      }
   }

   $total_products = implode(', ', $cart_products);

   $order_query = $conn->prepare("SELECT * FROM `orders` WHERE name = ? AND number = ? AND email = ? AND method = ? AND address = ? AND total_products = ? AND total_price = ?");
   $order_query->execute([$name, $number, $email, $method, $address, $total_products, $cart_total]);

   if ($cart_total == 0) {
      $message[] = 'your cart is empty';
   } elseif ($order_query->rowCount() > 0) {
      $message[] = 'order placed already!';
   } else {
      $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on) VALUES(?,?,?,?,?,?,?,?,?)");
      $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $cart_total, $placed_on]);
      $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
      $delete_cart->execute([$user_id]);
      $message[] = 'order placed successfully!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">

   <style>
      .modal {
         display: none;
         position: fixed;
         z-index: 1000;
         left: 0;
         top: 0;
         width: 100%;
         height: 100%;
         overflow: auto;
         background-color: rgba(0, 0, 0, 0.6);
      }

      .modal-content {
         background-color: #fff;
         margin: 10% auto;
         padding: 30px;
         border: none;
         border-radius: 12px;
         width: 90%;
         max-width: 500px;
         text-align: center;
         box-shadow: 0 10px 25px rgba(0,0,0,0.25);
         animation: slideIn 0.4s ease-out;
      }

      @keyframes slideIn {
         from { transform: translateY(-50px); opacity: 0; }
         to { transform: translateY(0); opacity: 1; }
      }

      .modal-content h2 {
         margin-bottom: 15px;
         font-size: 22px;
         color: #333;
      }

      .modal-content p {
         color: #666;
         margin-bottom: 20px;
      }

      .modal-content button {
         padding: 10px 20px;
         background-color: #e74c3c;
         color: white;
         border: none;
         border-radius: 8px;
         font-size: 16px;
         cursor: pointer;
         transition: background-color 0.3s ease;
      }

      .modal-content button:hover {
         background-color: #c0392b;
      }
   </style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="display-orders">
   <?php
   $cart_grand_total = 0;
   $select_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $select_cart_items->execute([$user_id]);
   if ($select_cart_items->rowCount() > 0) {
      while ($fetch_cart_items = $select_cart_items->fetch(PDO::FETCH_ASSOC)) {
         $cart_total_price = ($fetch_cart_items['price'] * $fetch_cart_items['quantity']);
         $cart_grand_total += $cart_total_price;
   ?>
      <p><?= $fetch_cart_items['name']; ?> <span>(<?= '$' . $fetch_cart_items['price'] . '/- x ' . $fetch_cart_items['quantity']; ?>)</span></p>
   <?php
      }
   } else {
      echo '<p class="empty">your cart is empty!</p>';
   }
   ?>
   <div class="grand-total">grand total : <span>$<?= $cart_grand_total; ?>/-</span></div>
</section>

<section class="checkout-orders">
   <form action="" method="POST" id="orderForm">
      <h3>place your order</h3>

      <div class="flex">
         <div class="inputBox">
            <span>your name :</span>
            <input type="text" name="name" placeholder="enter your name" class="box" required>
         </div>
         <div class="inputBox">
            <span>your number :</span>
            <input type="number" name="number" placeholder="enter your number" class="box" required>
         </div>
         <div class="inputBox">
            <span>your email :</span>
            <input type="email" name="email" placeholder="enter your email" class="box" required>
         </div>
         <div class="inputBox">
            <span>payment method :</span>
            <select name="method" class="box" required id="payment-method">
               <option value="cash on delivery">cash on delivery</option>
               <option value="credit card">credit card</option>
               <option value="paytm">paytm</option>
               <option value="paypal">paypal</option>
            </select>
         </div>
         <div class="inputBox">
            <span>address line 01 :</span>
            <input type="text" name="flat" placeholder="e.g. flat number" class="box" required>
         </div>
         <div class="inputBox">
            <span>address line 02 :</span>
            <input type="text" name="street" placeholder="e.g. street name" class="box" required>
         </div>
         <div class="inputBox">
            <span>city :</span>
            <input type="text" name="city" placeholder="e.g. mumbai" class="box" required>
         </div>
         <div class="inputBox">
            <span>state :</span>
            <input type="text" name="state" placeholder="e.g. maharashtra" class="box" required>
         </div>
         <div class="inputBox">
            <span>country :</span>
            <input type="text" name="country" placeholder="e.g. India" class="box" required>
         </div>
         <div class="inputBox">
            <span>pin code :</span>
            <input type="number" min="0" name="pin_code" placeholder="e.g. 123456" class="box" required>
         </div>
      </div>

      <input type="submit" name="order" class="btn <?= ($cart_grand_total > 1) ? '' : 'disabled'; ?>" value="place order">
   </form>
</section>

<!-- MODAL -->
<div id="paymentModal" class="modal">
   <div class="modal-content">
      <h2>Payment Method Not Available</h2>
      <p>Due to technical issues, this mode of payment is currently unavailable. Please select <strong>Cash on Delivery</strong> to continue.</p>
      <button onclick="closeModal()">Close</button>
   </div>
</div>

<?php include 'footer.php'; ?>

<script>
   let selectedMethod = "cash on delivery";

   const paymentSelect = document.getElementById('payment-method');
   const orderForm = document.getElementById('orderForm');

   paymentSelect.addEventListener('change', function () {
      selectedMethod = this.value.toLowerCase();
      if (selectedMethod !== 'cash on delivery') {
         document.getElementById('paymentModal').style.display = 'block';
      }
   });

   orderForm.addEventListener('submit', function (e) {
      if (selectedMethod !== 'cash on delivery') {
         e.preventDefault();
         document.getElementById('paymentModal').style.display = 'block';
      }
   });

   function closeModal() {
      document.getElementById('paymentModal').style.display = 'none';
   }
</script>

</body>
</html>
