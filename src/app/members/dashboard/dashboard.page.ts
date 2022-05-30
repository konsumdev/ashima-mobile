import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { MenuController } from '@ionic/angular';
import { Router } from '@angular/router';
import { DashboardProvider } from 'src/app/services/dashboard/dashboard';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { StatusBar } from '@ionic-native/status-bar/ngx';

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.page.html',
    styleUrls: ['./dashboard.page.scss'],
})
export class DashboardPage implements OnInit {

    toggleIsOnHrs: string = 'ios-arrow-up';
    toggleIsOnLvs: string = 'ios-arrow-down';
    toggleIsOnPC: string = 'ios-arrow-up';
    dashHours: any = "1";
    dashLeaves: any = "0";
    dashPaycheck: any = "1";
    selectedTheme: any = "ashima-body-cont";
    myDate: String = new Date().toISOString();
    time: Date = new Date();
    prevNowPlaying: any;
    timeIn: any;
    lunchIn: any;
    lunchOut: any;
    timeOut: any;
    timesheetCounts: any;
    missedPunch: any;
    attendance: any;
    nextShift: any;
    payslips: any;
    nextPay: any;
    leaveCredits: any;
    myDataSet: any = [];
    chartOptions: any;
    krispyKremeData: any = [];
    platformType: any;

    constructor(
        private mainApp : AppComponent,
        private menu: MenuController,
        private router: Router,
        private dashProv: DashboardProvider,
        private statusBar: StatusBar,
        public comCtrl: MyCommonServices
    ) { 

        this.timesheetCounts = {
            'missing':0,
            'pending':0,
            'rejected':0
        };
        this.attendance = {
            'absences':0,
            'tardiness':0,
            'undertime':0
        };
        this.nextShift = {
            'shift_name':null,
            'shift_date':null,
            'start_time':null,
            'end_time':null,
            'flexible':null,
            'required_login':null
        }
        this.nextPay = {
            'first_payroll_date':null,
            'cut_off_from':null,
            'cut_off_to':null
        };

        setInterval(() => {
            this.time = new Date();
        }, 1);
    }

    ngOnInit() {
        this.statusBar.show();        
        this.menu.enable(false, 'managerMenu');
        this.menu.enable(true, 'employeeMenu');
        this.menu.close();

        this.mainApp.apiSessionChecker();
        this.krispyKreme();
    }

    goto(url: any) {
        this.router.navigate([url]);
    }

    public doughnutChartLabels:string[] = ['Remaining', 'Used'];
    public doughnutChartType:string = 'doughnut';
    public krispyKremeColors = [
        {
            backgroundColor: ['#1172AD', '#e4e4e4'],
            pointHoverBackgroundColor: ['#72accf', '#dadada'],
        }
    ];
    public kripyKremeOpts:any = {
        legend: {
            display: false
        },
        responsive: true,
        maintainAspectRatio: true,
        segmentShowStroke: false,
        elements: {
            arc: {
            borderWidth: 0,
            }
        },
        cutoutPercentage: 65
    };

    public chartClicked(e:any):void {
        console.log(e);
    }

    public chartHovered(e:any):void {
        console.log(e);
    }

    krispyKreme() {
        this.dashProv.leaveDoughnut().then(res=>{
            if (res) {
            this.leaveCredits = res;
            this.toggleIsOnLvs = 'ios-arrow-up';
            this.dashLeaves = '1';
            }
        }).catch(err=>{
            // this.comCtrl.presentToast('', 'api_error');
            console.log();
        });
    }

    offToggle(type) {
        if (type == "hours") {
            if (this.dashHours == '0') {
                this.toggleIsOnHrs = 'ios-arrow-up';
                this.dashHours = '1';
            } else {
                this.toggleIsOnHrs = 'ios-arrow-down';
                this.dashHours = '0';
            }
        } else if (type == "leaves") {
            if (this.dashLeaves == '0') {
                this.toggleIsOnLvs = 'ios-arrow-up';
                this.dashLeaves = '1';
            } else {
                this.toggleIsOnLvs = 'ios-arrow-down';
                this.dashLeaves = '0';
            }
        } else if (type == "paycheck") {
            if (this.dashPaycheck == '0') {
                this.toggleIsOnPC = 'ios-arrow-up';
                this.dashPaycheck = '1';
            } else {
                this.toggleIsOnPC = 'ios-arrow-down';
                this.dashPaycheck = '0';
            }
        }
    }

}
