<div class="altdata-block head1">
    <h1><?= $title ?? 'Альтернативные данные' ?></h1>

    <!-- Селектор компании/тикера -->
    <form class="ticker-form" method="get" action="">
        <label for="symbol">Компания:</label>
        <select name="symbol" id="symbol">
            <?php foreach ($symbols as $code => $name): ?>
                <option value="<?= $code ?>"<?= ($selectedSymbol ?? '') === $code ? ' selected' : '' ?>>
                    <?= htmlspecialchars($name['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Показать</button>
    </form>

    <!-- Раздел Вакансии -->
    <section class="alt-section alt-vacancies-summary">
        <h2>Сводная информация по вакансиям (<?= count($vacancies) ?>)</h2>

        <div class="vacancy-summary-group">
            <h3>Топ работодателей</h3>
            <ul>
                <?php foreach ($topEmployers as $employer => $count): ?>
                    <li><?= htmlspecialchars($employer) ?> — <?= $count ?> вакансий</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="vacancy-summary-group">
            <h3>Топ городов</h3>
            <ul>
                <?php foreach ($topCities as $city => $count): ?>
                    <li><?= htmlspecialchars($city) ?> — <?= $count ?> вакансий</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="vacancy-summary-group">
            <h3>Распределение по дате публикации</h3>
            <ul>
                <?php foreach ($publishStats as $period => $count): ?>
                    <li><?= htmlspecialchars($period) ?> — <?= $count ?> вакансий</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="vacancy-summary-group">
            <h3>Зарплатные вилки (KZT)</h3>
            <ul>
                <?php foreach ($salaryStats as $range => $count): ?>
                    <li><?= htmlspecialchars($range) ?> — <?= $count ?> вакансий</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Раздел Новости -->
    <section class="alt-section alt-news">
        <h2>Новости</h2>
        <?php if (!empty($news)): ?>
            <ul class="news-list">
                <?php foreach ($news as $item): ?>
                    <li>
                        <strong><?= htmlspecialchars($item['title'] ?? '-') ?></strong>
                        <?php if (!empty($item['published_at'])): ?>
                            <span class="news-date"><?= htmlspecialchars($item['published_at']) ?></span>
                        <?php endif; ?>
                        <br>
                        <?= htmlspecialchars($item['source'] ?? '') ?>
                        <?php if (!empty($item['url'])): ?>
                            [<a href="<?= htmlspecialchars($item['url']) ?>" target="_blank">Читать</a>]
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert info">Нет новостей по выбранной компании.</div>
        <?php endif; ?>
    </section>

    <!-- Раздел События -->
    <section class="alt-section alt-events">
        <h2>События</h2>
        <?php if (!empty($events)): ?>
            <ul class="event-list">
                <?php foreach ($events as $ev): ?>
                    <li>
                        <strong><?= htmlspecialchars($ev['title'] ?? '-') ?></strong>
                        <?php if (!empty($ev['date'])): ?>
                            <span class="event-date"><?= htmlspecialchars($ev['date']) ?></span>
                        <?php endif; ?>
                        <br>
                        <?= htmlspecialchars($ev['description'] ?? '') ?>
                        <?php if (!empty($ev['url'])): ?>
                            [<a href="<?= htmlspecialchars($ev['url']) ?>" target="_blank">Подробнее</a>]
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert info">Нет событий для выбранной компании.</div>
        <?php endif; ?>
    </section>
</div>