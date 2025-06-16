<?php include 'header.php'; ?>
<style>
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
  font-family: sans-serif;
}

.section-title {
  font-size: 24px;
  margin-bottom: 20px;
}

.flex-row {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.card h3 {
  margin-top: 0;
}

.grid-2-1 {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.card-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.card {
  border: 1px solid #e0e0e0;
  padding: 20px;
  border-radius: 4px;
}
</style>

<div class="container">

  <!-- О нас и Преимущества -->
  <div class="grid-2-1">
    <div class="card">
      <h1 class="sp">О системе</h1>
      <p>Это веб‑панель управления для малого и среднего бизнеса. Она объединяет регистрацию и учёт компаний, сотрудников, систем налогообложения и финансовых показателей в едином интерфейсе.</p>
    </div>
    <div class="card">
      <h1 class="sp">Преимущества</h1>
      <ul>
        <li>Единая платформа для кадрового и финансового учёта</li>
        <li>Гибкое разграничение прав доступа (директор, бухгалтер, менеджер)</li>
        <li>Автоматическая генерация кодов‑приглашений для сотрудников</li>
        <li>Поддержка различных систем налогообложения</li>
        <li>Интегрированный отчётный генератор</li>
      </ul>
    </div>
  </div>

  <!-- Функционал системы -->
  <div style="margin-top: 60px;">
    <h2 class="section-title">Функционал системы</h2>
    <div class="card-container">
      <div class="card news">
        <h3>Регистрация и роли</h3>
        <p>Директор создаёт компанию и получает код‑приглашение. Сотрудники регистрируются по коду, выбирают должность и получают доступ в личный кабинет.</p>
      </div>
      <div class="card news">
        <h3>Управление компаниями</h3>
        <p>Редактирование данных компании: название, ОГРН/ИНН, адрес, статус малого предприятия.</p>
      </div>
      <div class="card news">
        <h3>Кадровый учёт</h3>
        <p>Справочник должностей, учёт сотрудников, хранение ФИО, email, логина, пароля и суммы зарплаты.</p>
      </div>
      <div class="card news">
        <h3>Финансовый учёт</h3>
        <p>Ведение доходов и расходов с привязкой к системе налогообложения, автоматический расчёт налоговых обязательств.</p>
      </div>
      <div class="card news">
        <h3>Отчёты</h3>
        <p>Генерация отчётов по выбранным параметрам и периодам, экспорт в PDF и удобный просмотр в браузере.</p>
      </div>
    </div>
  </div>

  <!-- Отзывы -->
  <div style="margin-top: 60px;">
    <h2 class="section-title">Отзывы клиентов</h2>
    <div class="card-container">
      <div class="card review">
        <h3>Марина К.</h3>
        <p>Простой и понятный интерфейс помог нам быстро организовать учёт сотрудников и финансов без долгого обучения.</p>
      </div>
      <div class="card review">
        <h3>Александр П.</h3>
        <p>Отличный отчётный модуль: выгрузка в PDF экономит время и делает нашу работу максимально прозрачной.</p>
      </div>
      <div class="card review">
        <h3>Ольга С.</h3>
        <p>Поддержка нескольких систем налогообложения — именно то, что искали для нашего бизнеса.</p>
      </div>
    </div>
  </div>

</div>
