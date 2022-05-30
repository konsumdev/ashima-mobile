import { Component, OnInit, ViewChild } from '@angular/core';
import { CalendarComponent } from 'ionic2-calendar/calendar';
import { ShiftsProvider } from 'src/app/services/shifts/shifts';
import { AppComponent } from 'src/app/app.component';
import { formatDate } from '@angular/common';

@Component({
  selector: 'app-shift',
  templateUrl: './shift.page.html',
  styleUrls: ['./shift.page.scss'],
})
export class ShiftPage implements OnInit {
  eventSource: any;
  viewTitle: any;
  selectedDate: any;
  selectedShift: any = "";
  nextShift: any = "";

  isToday:boolean;
  calendar = {
      mode: 'month',
      currentDate: new Date(),
      dateFormatter: {
          formatMonthViewDay: function(date:Date) {
              return date.getDate().toString();
          },
          formatMonthViewDayHeader: function(date:Date) {
              return 'MonMH';
          },
          formatMonthViewTitle: function(date:Date) {
              return 'testMT';
          },
          formatWeekViewDayHeader: function(date:Date) {
              return 'MonWH';
          },
          formatWeekViewTitle: function(date:Date) {
              return 'testWT';
          },
          formatWeekViewHourColumn: function(date:Date) {
              return 'testWH';
          },
          formatDayViewHourColumn: function(date:Date) {
              return 'testDH';
          },
          formatDayViewTitle: function(date:Date) {
              return 'testDT';
          }
      }
  };

  constructor(
    private shiftProv: ShiftsProvider,
    private mainApp : AppComponent,
  ) { 
    this.mainApp.apiSessionChecker();
    let now = new Date();
    this.viewTitle = formatDate(now, 'MMMM yyyy', 'en-US');
    console.log(this.viewTitle);
  }

  ngOnInit() {
    this.loadEvents();
  }

  getDateWorkSched() {
      this.shiftProv.getWorkSched(this.selectedDate).then(res=> {
          console.log(res);
          let rslt: any = res;
          if (rslt) {
              this.selectedShift = rslt;
          }
      }).catch(error=> {
          console.log(error);
      });
  }

  getScheduleNextDay() {
      this.shiftProv.getNextSched(this.selectedDate).then(res=>{
          console.log(res);
          let rslt: any = res;
          if (rslt) {
              this.nextShift = rslt;
          }
      }).catch(error=>{
          console.log(error);
      });
  }

  loadEvents() {
      this.eventSource = this.createRandomEvents();
  }

  onViewTitleChanged(title: any) {
      this.viewTitle = title;
  }

  onEventSelected(event: any) {
      console.log('Event selected:' + event.startTime + '-' + event.endTime + ',' + event.title);
  }

  changeMode(mode: any) {
      this.calendar.mode = mode;
  }

  today() {
      this.calendar.currentDate = new Date();
  }

  onTimeSelected(ev: any) {
      
      console.log('selectedtime: '+ev.selectedTime);
      var mement = formatDate(ev.selectedTime, 'yyyy-MM-dd', 'en-US'); // moment(ev.selectedTime).format("YYYY-MM-DD");
      this.selectedDate = mement;        
      this.getDateWorkSched();        
  }

  onCurrentDateChanged(event:Date) {
      var today = new Date();
      today.setHours(0, 0, 0, 0);
      event.setHours(0, 0, 0, 0);
      this.isToday = today.getTime() === event.getTime();
  }

  createRandomEvents() {
      var events = [];
      var date = new Date();
      // var eventType = 1;
      var startDay = 0;
      var endDay = 0;
      var startTime: any;
      var endTime: any;
      startTime = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate() + startDay));
      if (endDay === startDay) {
          // endDay += 1;
      }
      endTime = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate() + endDay));
      events.push({
          title: 'Test Event',
          startTime: startTime,
          endTime: endTime,
          allDay: true
      });
      return events;
  }

  onRangeChanged(ev: any) {
      console.log('range changed: startTime: ' + ev.startTime + ', endTime: ' + ev.endTime);
  }

  markDisabled = (date:Date) => {
      var current = new Date();
      current.setHours(0, 0, 0);
      return date < current;
  };

}
