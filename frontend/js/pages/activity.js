import { activityApi } from '../api/index.js';

// Activity displays the backend-calculated balance and transaction history.
const pointsElement = document.querySelector('.points p');
const listElement = document.querySelector('main section:last-of-type ul');

function render(response) {
    pointsElement.textContent = `Total earned points: ${response.balance}`;
    listElement.innerHTML = '';

    if (!response.activity.length) {
        listElement.innerHTML = '<li>No activity yet.</li>';
        return;
    }

    response.activity.forEach((entry) => {
        const item = document.createElement('li');
        item.textContent = `${entry.type}: ${entry.amount > 0 ? '+' : ''}${entry.amount} points`;
        listElement.append(item);
    });
}

activityApi.list()
    .then(render)
    .catch((error) => {
        pointsElement.textContent = error.status === 401
            ? 'Please log in to view your activity.'
            : 'Activity could not be loaded.';
    });
