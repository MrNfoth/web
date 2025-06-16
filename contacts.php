<?php
  $title = "Контакты";
  $description = "Контактная информация";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $title; ?></title>
  <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
</head>
<body>
    <?php include 'header.php'; ?>
  <div class="container">
    <h1>Контакты</h1>
    <p class="text">Адрес: Большая Морская ул., 67, Санкт-Петербург</p>
    <p class="text">Телефон: +7 (812) 123-45-67</p>
    <p class="text">Email: <a href="mailto:mycompany@mail.ru">mycompany@mail.ru</a></p>
    <div id="map" style="width: 100%; height: 600px;"></div>
    <script>
      ymaps.ready(init);

      function init() {
        var myMap = new ymaps.Map("map", {
          center: [59.929560, 30.296671],
          zoom: 17
        });

        var myPlacemark = new ymaps.Placemark([59.929560, 30.296671], {
          hintContent: "Офис",
          balloonContent: "Большая Морская ул., 67, Санкт-Петербург"
        });

        myMap.geoObjects.add(myPlacemark);
      }
    </script>
  </div>
</body>
</html>