import { Component, OnInit, ElementRef, ViewChild } from '@angular/core';
import { ActivatedRoute, Router, NavigationExtras } from '@angular/router';
import { HoursProvider } from 'src/app/services/hours/hours';
import { Chart } from 'chart.js';

@Component({
  selector: 'app-timesheet-details',
  templateUrl: './timesheet-details.page.html',
  styleUrls: ['./timesheet-details.page.scss'],
})
export class TimesheetDetailsPage implements OnInit {

  @ViewChild("doughnutCanvas",{static:false}) doughnutCanvas: ElementRef;

  data: any;

  barChart: any;
  doughnutChart: Chart;
  lineChart: any;

  title: any = 'Timesheet Details';
  passedDetails: any;
  workHrs: any;
  missHrs: any;
  approvers: any;
  currentDate: any;
  hasBreakOne: boolean = false;
  hasBreakTwo: boolean = false;
  hasLunch: boolean = false;
  submitText: string = "Change";
  shiftName: string = "";
  timeinStatus: any = "";

  constructor(
    private route: ActivatedRoute, 
    private router: Router,
    private hoursProv: HoursProvider,
  ) { 
    this.route.queryParams.subscribe(params => {
      if (this.router.getCurrentNavigation().extras.state) {
        this.passedDetails = this.router.getCurrentNavigation().extras.state.details;
      }
    });

    this.workHrs = 0;
    this.missHrs = 0;
  }

  ngOnInit() {
    console.log(this.passedDetails);
    if (this.passedDetails) {
        // this.title = moment(this.passedDetails.date).format("DD-MMM-YY");
        this.workHrs = parseFloat(this.passedDetails.total_hours);
        this.missHrs = parseFloat(this.passedDetails.total_hours_required) - parseFloat(this.passedDetails.total_hours);
        this.missHrs = (this.missHrs < 0) ? 0 : this.missHrs;
        this.timeinStatus = false;
        var last_source = this.passedDetails.last_source;

        if (this.passedDetails.time_in_status == "pending") {
            this.timeinStatus = true;
        }

        if (this.passedDetails.time_in_status == "approved") {
            if (last_source == 'adjusted' || last_source == 'Adjusted') {
                this.timeinStatus = true;
            }
        }

        
        // get approvers
        this.getApprovers(
            this.passedDetails.employee_time_in_id,
            this.passedDetails.last_source,
            this.passedDetails.time_in_status,
            this.passedDetails.change_log_date_filed,
            this.passedDetails.source
        );
        
        if (this.passedDetails.rest_day_r_a == "yes") {
            this.submitText = "Apply RD";
        } else if (this.passedDetails.holiday_approve == "yes") {
            this.submitText = "Apply HOL";
        }

        this.shiftName = this.passedDetails.name;
        if (this.passedDetails.work_schedule_id == "-1") {
            this.shiftName = "Rest Day";
        } else if (this.passedDetails.work_schedule_id == "-2") {
            this.shiftName = "Excess Hours";
        }
        
        if (this.passedDetails.is_holiday) {
            this.shiftName = this.passedDetails.holiday_name + " - " + this.passedDetails.holiday_type;
        }

        this.makeDoughnuts();
        
    }
  }

  gotoBack() {
    this.router.navigate(['/members/timesheets']);
  }

  changeLogs() {
    let navigationExtras: NavigationExtras = {
      state: {
        details: this.passedDetails
      }
    };
    this.router.navigate(['/members/change-logs'], navigationExtras);
  }

  makeDoughnuts() {
    var ctx = (<any>document.getElementById('doughnutCanvas')).getContext('2d');
    this.doughnutChart = new Chart(ctx, {
 
      type: 'doughnut',
      data: {
          labels: ["Worked", "Undertime"],
          datasets: [{
              label: 'Hours worked',
              data: [this.workHrs, this.missHrs],
              backgroundColor: [
                  'rgba(255, 255, 255)',
                  'rgb(174,174,174)',
              ],
              hoverBackgroundColor: [
                  'rgba(255, 255, 255, 0.5)',
                  'rgb(174,174,174, 0.5)',
              ]
          }]
      },      
      options: {
          legend: {
              display: false
          },
          animation: {
            duration: 2000,
            easing: 'easeInQuart'
          },
          responsive: true,
          maintainAspectRatio: false,
          elements: {
              arc: {
                borderWidth: 0,
              }
          },
          cutoutPercentage: 65,
      }

    });
  }

  getApprovers(timeInId: any, lastSource: any, status: any, dateFiled: any, source: any) {
    this.hoursProv.getApproversNameAndStatus(
        timeInId, lastSource, status, dateFiled, source
    ).then(res=>{
        let rslt: any = res;
        if (rslt) {
            this.approvers = rslt;                
        }            
    }).catch(err=>{
        console.log(err);
    });
  }

}
