<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION["admin"])) {
    die(json_encode(["status" => "error", "message" => "Access Denied"]));
}

$action = $_GET["action"] ?? null;

// Получение списка товаров
if ($action === "get_products") {
    $products = [];
    $result = $conn->query("SELECT * FROM products");
    while ($row = $result->fetch_assoc()) {
        $row["image"] = json_decode($row["image"], true);
        $row["main_image"] = $row["image"][0] ?? 'no-image.png';
        $products[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "products" => $products]);
    exit;
}


if ($action === "add_product" && isset($_POST["name"], $_POST["description"], $_POST["price"])) {

    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $category = $_POST["category"] ?? 'Unisex';

    if (!is_dir('uploads/')) {
        mkdir('uploads/', 0777, true);
    }

    $image_paths = [];

    if (!empty($_FILES["image"]["name"][0])) {
        foreach ($_FILES["image"]["tmp_name"] as $key => $tmp_name) {
            $file_tmp = $_FILES["image"]["tmp_name"][$key];
            $file_type = mime_content_type($file_tmp);
            $file_size = $_FILES["image"]["size"][$key];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file_type, $allowed_types) || $file_size > 2 * 1024 * 1024) {
                continue;
            }

            $extension = pathinfo($_FILES["image"]["name"][$key], PATHINFO_EXTENSION);
            $file_name = uniqid("img_", true) . '.' . strtolower($extension);
            $target_file = "uploads/" . $file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_paths[] = $file_name;
            }
        }
    }

    $image_json = json_encode($image_paths);

    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $name, $description, $price, $category, $image_json);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Product added"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add product"]);
    }
    exit;
}

if ($action === "delete_product" && isset($_POST["id"])) {
    $id = intval($_POST["id"]);
    $conn->query("DELETE FROM products WHERE id = $id");
    echo json_encode(["status" => "success", "message" => "Product deleted"]);
    exit;
}

if ($action === "delete_order" && isset($_POST["order_id"])) {
    $order_id = intval($_POST["order_id"]);

    $conn->begin_transaction();

    try {
        $conn->query("DELETE FROM order_items WHERE order_id = $order_id");
        $conn->query("DELETE FROM payments WHERE order_id = $order_id");
        $conn->query("DELETE FROM orders WHERE id = $order_id");

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Order deleted"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "DB error: " . $e->getMessage()]);
    }
    exit;
}


if ($action === "edit_product" && isset($_POST["id"])) {
    $id = intval($_POST["id"]);
    $name = $conn->real_escape_string($_POST["name"]);
    $price = floatval($_POST["price"]);
    $description = $conn->real_escape_string($_POST["description"]);


    $existing_images = $_POST["existing_images"] ?? [];
    if (!is_array($existing_images)) {
        $existing_images = [];
    }

    $new_image_paths = [];


    if (!empty($_FILES["image"]["name"][0])) {
        foreach ($_FILES["image"]["tmp_name"] as $key => $tmp_name) {
            $file_tmp = $_FILES["image"]["tmp_name"][$key];
            $file_type = mime_content_type($file_tmp);
            $file_size = $_FILES["image"]["size"][$key];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file_type, $allowed_types) || $file_size > 2 * 1024 * 1024) {
                continue;
            }

            $extension = pathinfo($_FILES["image"]["name"][$key], PATHINFO_EXTENSION);
            $file_name = uniqid("img_", true) . '.' . strtolower($extension);
            $target_file = "uploads/" . $file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $new_image_paths[] = $file_name;
            }
        }
    }


    $final_images = array_merge($existing_images, $new_image_paths);
    $images_json = json_encode($final_images);

    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sdssi", $name, $price, $description, $images_json, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Product updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Update failed"]);
    }

    exit;
}


$conn->close();