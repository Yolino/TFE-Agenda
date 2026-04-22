import "./bootstrap";
import Holidays from "date-holidays";

window.Holidays = Holidays;

window.crocheuxHolidays = function(weekDates) {
    return {
        map: {},
        init() {
            const hd = new Holidays('BE');
            const years = [...new Set(weekDates.map(d => +d.slice(0, 4)))];
            const built = {};
            years.forEach(y =>
                (hd.getHolidays(y) || []).forEach(h => {
                    if (h.type === 'public') built[h.date.slice(0, 10)] = h.name;
                })
            );
            this.map = built;
        },
        isHoliday(date) { return !!this.map[date]; },
        getName(date)   { return this.map[date] || ''; },
    };
};
