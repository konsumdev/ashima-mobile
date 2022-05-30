import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { MyCommonServices } from 'src/app/shared/myCommonServices';
import { HoursProvider } from 'src/app/services/hours/hours';
import { AppComponent } from 'src/app/app.component';
import { Router, NavigationExtras } from '@angular/router';

@Component({
  selector: 'app-timesheets',
  templateUrl: './timesheets.page.html',
  styleUrls: ['./timesheets.page.scss'],
})
export class TimesheetsPage implements OnInit {

  tsheets: any;
  allTsheets: any;
  allPage: any = "1";
  allTotal: any;
  pendingTsheets: any;
  pendingPage: any = "1";
  pendingTotal: any;
  rejectTsheets: any;
  rejectPage: any = "1";
  rejectTotal: any;
  fetchingAll: boolean;
  fetchingPending: boolean;
  fetchingReject: boolean;
  fakeUsers: Array<any>;

  constructor(
    public comCtrl: MyCommonServices,
    private hoursProv: HoursProvider,
    private mainApp : AppComponent,
    private router: Router,
  ) {
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
    this.fetchingPending = true;
    this.fetchingReject = true;
  }

  ngOnInit() {
    this.tsheets = 'all';
    this.allTsheets = "";
    this.pendingTsheets = "";
    this.rejectTsheets = "";
    this.getAllTimesheets();
  }

  getAllTimesheets() {
    // let spnr = this.comCtrl.presentLoading('Updating list ...');
    this.hoursProv.viewAttendanceLogs(this.allPage).then(res=>{            
        let rslt: any = res;
        this.allTsheets = rslt.list;
        this.allTotal = parseInt(rslt.total, 10);
        this.allPage = parseInt(rslt.page, 10);
        this.fetchingAll = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingAll = false;
    });
}

  pendingClick() {
    if (!this.pendingTsheets) {
      this.getPendingTimesheets();
    }
  }

  getPendingTimesheets() {
    this.hoursProv.viewAttendancePending(this.pendingPage).then(res=>{
      let rslt: any = res;
      this.pendingTsheets = rslt.list;
      this.pendingTotal = parseInt(rslt.total, 10);
      this.pendingPage = parseInt(rslt.page, 10);
      this.fetchingPending = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingPending = false;
    });
  }

  rejectClick() {
    if (!this.rejectTsheets) {
      this.getRecjecTimesheets();
    }
  }

  getRecjecTimesheets() {
    this.hoursProv.viewAttendanceReject(this.rejectPage).then(res=>{
      let rslt: any = res;
      this.rejectTsheets = rslt.list;
      this.rejectTotal = parseInt(rslt.total, 10);
      this.rejectPage = parseInt(rslt.page, 10);
      this.fetchingReject = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingReject= false;
    });
  }

  doRefreshPending(event: any) {
    this.pendingPage = "1";

    setTimeout(() => {
      this.hoursProv.viewAttendancePending(this.pendingPage).then(res=>{
          // reset data
          this.pendingTsheets = "";
          this.pendingTotal = "";

          let rslt: any = res;
          this.pendingTsheets = rslt.list;
          this.pendingTotal = parseInt(rslt.total, 10);
          this.pendingPage = parseInt(rslt.page, 10);
          
          event.target.complete();
      }).catch(err=>{
          console.log(err);          
          event.target.complete();
      });
      
    }, 2000);
  }
  
  doRefresh(event: any) {
    this.allPage = "1";

    setTimeout(() => {
      this.hoursProv.viewAttendanceLogs(this.allPage).then(res=>{
        // reset data
        this.allTsheets = "";
        this.allTotal = "";

        let rslt: any = res;
        this.allTsheets = rslt.list;
        this.allTotal = parseInt(rslt.total, 10);
        this.allPage = parseInt(rslt.page, 10);
        event.target.complete();
    }).catch(err=>{
        console.log(err);
        this.fetchingAll = false;
        event.target.complete();
    });
      
    }, 2000);
  }

  doRefreshReject(event: any) {
    this.rejectPage = "1";
    setTimeout(() => {
      this.hoursProv.viewAttendanceReject(this.rejectPage).then(res=>{
        // reset data
        this.rejectPage = "";
        this.rejectTotal = "";

        let rslt: any = res;
        this.rejectTsheets = rslt.list;
        this.rejectTotal = parseInt(rslt.total, 10);
        this.rejectTotal = parseInt(rslt.page, 10);
        event.target.complete();
    }).catch(err=>{
        console.log(err);
        event.target.complete();
    });
      
    }, 2000);
  }

  gotoDetails(data: any) {
    let navigationExtras: NavigationExtras = {
      state: {
        details: data
      }
    };
    this.router.navigate(['/members/timesheet-details'], navigationExtras);
  }

  addLogs() {
    this.router.navigate(['/members/add-timesheet']);
  }

  doInfiniteAll(event: any) {
    this.allPage = this.allPage+1;
    setTimeout(() => {
      this.hoursProv.viewAttendanceLogs(this.allPage+'').then( res => {
        let rslt: any = res;

        for(let i=0; i<rslt.list.length; i++) {
            this.allTsheets.push(rslt.list[i]);
        }
        event.target.complete();
      }).catch(error => {
        event.target.complete();
        console.log(error);
      });
      

      // App logic to determine if all data is loaded
      // and disable the infinite scroll
      if (this.allPage == this.allTotal) {
        event.target.disabled = true;
      }
    }, 500);
  }
  
}
