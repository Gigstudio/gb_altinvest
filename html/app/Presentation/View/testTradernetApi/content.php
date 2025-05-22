<div class="content-block">
    <h1><?= $title ?? 'Quotes' ?></h1>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="ticker-form" method="get" action="">
        <label for="symbol">Тикер:</label>
        <select name="symbol" id="symbol">
            <?php foreach ($symbols as $code => $name): ?>
                <option value="<?= $code ?>"<?= ($symbol ?? '') === $code ? ' selected' : '' ?>>
                    <?= htmlspecialchars($name) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Показать</button>
    </form>

    <?php if (!empty($quotes)): ?>
        <div class="quotes-table-wrapper">
            <table class="quotes-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Открытие</th>
                        <th>Макс</th>
                        <th>Мин</th>
                        <th>Закрытие</th>
                        <th>Объем</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotes as $q): ?>
                        <tr>
                            <td><?= htmlspecialchars($q['date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($q['open'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($q['high'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($q['low'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($q['close'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($q['volume'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif (isset($quotes)): ?>
        <div class="alert info">Нет данных для отображения.</div>
    <?php endif; ?>
</div>
