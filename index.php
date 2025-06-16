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

.card.news, .card.review {
  width: 100%;
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
</style>

<div class="container">

  <!-- О нас и Преимущества -->
  <div class="grid-2-1">
    <div class="card">
      <h1 class="sp">О нас</h1>
      <p>Мы — компания, специализирующаяся на генерации отчётов любой сложности. Наши решения автоматизируют сбор, анализ и оформление данных, экономя ваше время и ресурсы.</p>
    </div>
    <div class="card">
      <h1 class="sp">Преимущества</h1>
      <ul>
        <li>Автоматизация отчётности</li>
        <li>Гибкие настройки под клиента</li>
        <li>Надёжные алгоритмы генерации</li>
        <li>Интеграция с внешними сервисами</li>
        <li>Поддержка и сопровождение</li>
      </ul>
    </div>
  </div>

  <!-- Новости -->
  <div style="margin-top: 60px;">
    <h2 class="section-title">Новости</h2>
    <div class="card-container">
      <div class="card news">
        <h3>Запущен новый модуль экспорта</h3>
        <p>С 1 июня доступен модуль экспорта отчётов в PDF. Теперь вы можете быстро сохранять документы в нужном формате и делиться ими с коллегами.</p>
      </div>
      <div class="card news">
        <h3>Обновление интерфейса</h3>
        <p>Мы упростили навигацию и обновили дизайн панели управления. Интерфейс стал более лёгким и интуитивно понятным.</p>
      </div>
    </div>
  </div>

  <!-- Отзывы -->
  <div style="margin-top: 60px;">
    <h2 class="section-title">Отзывы клиентов</h2>
    <div class="card-container">
      <div class="card review">
        <h3>Марина К.</h3>
        <p>Сервис по генерации отчётов стал для нас настоящим открытием. Отчёты собираются автоматически, и нам больше не нужно тратить часы на рутину.</p>
      </div>
      <div class="card review">
        <h3>Александр П.</h3>
        <p>Очень удобно. Интерфейс простой, всё понятно, даже не пришлось обращаться в техподдержку. Отличный продукт!</p>
      </div>
      <div class="card review">
        <h3>Ольга С.</h3>
        <p>Используем решение в отделе аналитики. Интеграция с 1С и возможность тонкой настройки отчётов — прям то, что искали.</p>
      </div>
    </div>
  </div>

</div>
