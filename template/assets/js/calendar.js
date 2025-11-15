/**
 * Calendar JavaScript
 * 
 * @package Template Academy
 */

document.addEventListener('DOMContentLoaded', function() {
    const calendarToggle = document.getElementById('ak-calendar-toggle');
    const calendarSidebar = document.getElementById('ak-calendar-sidebar');
    const calendarClose = document.getElementById('ak-calendar-close');
    const calendarOverlay = document.getElementById('ak-calendar-overlay');
    
    if (calendarToggle) {
        calendarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            openCalendar();
        });
    }
    
    if (calendarClose) {
        calendarClose.addEventListener('click', closeCalendar);
    }
    
    if (calendarOverlay) {
        calendarOverlay.addEventListener('click', closeCalendar);
    }
    
    function openCalendar() {
        calendarSidebar.classList.add('active');
        calendarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Initialize calendar if not already done
        if (!document.querySelector('#ak-calendar .calendar-grid')) {
            initializeCalendar();
        }
    }
    
    function closeCalendar() {
        calendarSidebar.classList.remove('active');
        calendarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function initializeCalendar() {
        const calendarContainer = document.getElementById('ak-calendar');
        if (!calendarContainer) return;
        
        const now = new Date();
        const currentMonth = now.getMonth();
        const currentYear = now.getFullYear();
        
        const monthNames = [
            'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
        ];
        
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        
        let html = '<div class="calendar-header">';
        html += '<h4>' + monthNames[currentMonth] + ' ' + currentYear + '</h4>';
        html += '</div>';
        
        html += '<div class="calendar-grid">';
        html += '<div class="calendar-weekdays">';
        ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'].forEach(day => {
            html += '<div class="calendar-weekday">' + day + '</div>';
        });
        html += '</div>';
        
        html += '<div class="calendar-days">';
        
        // Empty cells before first day
        const startDay = firstDay === 0 ? 6 : firstDay - 1;
        for (let i = 0; i < startDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }
        
        // Days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const isToday = (day === now.getDate() && currentMonth === now.getMonth() && currentYear === now.getFullYear());
            html += '<div class="calendar-day' + (isToday ? ' today' : '') + '">' + day + '</div>';
        }
        
        html += '</div></div>';
        
        calendarContainer.innerHTML = html;
        
        // Add calendar styles
        const style = document.createElement('style');
        style.textContent = `
            .calendar-header { padding: 1rem 0; text-align: center; border-bottom: 1px solid #e5e5e5; }
            .calendar-header h4 { margin: 0; }
            .calendar-grid { margin-top: 1rem; }
            .calendar-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; margin-bottom: 0.5rem; }
            .calendar-weekday { text-align: center; font-size: 0.875rem; font-weight: 600; padding: 0.5rem; }
            .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
            .calendar-day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 50%; cursor: pointer; transition: background 0.3s ease; }
            .calendar-day:not(.empty):hover { background: #f5f5f5; }
            .calendar-day.today { background: #000; color: #fff; font-weight: 600; }
            .calendar-day.empty { cursor: default; }
        `;
        document.head.appendChild(style);
    }
});
