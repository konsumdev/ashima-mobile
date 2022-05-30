import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { HoursProvider } from 'src/app/services/hours/hours';
import { Router, NavigationExtras } from '@angular/router';

@Component({
  selector: 'app-overtime',
  templateUrl: './overtime.page.html',
  styleUrls: ['./overtime.page.scss'],
})
export class OvertimePage implements OnInit {
  fakeUsers: Array<any>;

  tsheets: any;
  allOt: any;
  fetchingAll: boolean = true;
  allPage: any = "1";
  allTotal: any;

  allApprove: any;
  fetchingApprove: boolean = true;
  approvePage: any = "1";
  approveTotal: any;

  allPending: any;
  fetchingPending: boolean = true;
  pendingPage: any = "1";
  pendingTotal: any;

  allReject: any;
  fetchingReject: boolean = true;
  rejectPage: any = "1";
  rejectTotal: any;

  constructor(
    private mainApp : AppComponent,
    private hoursProv: HoursProvider,
    private router: Router,
  ) {
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.tsheets = 'all';
    this.fetchingAll = true;
    this.fetchingPending = true;
    this.fetchingApprove = true;
    this.fetchingReject = true;
  }

  ngOnInit() {
    this.tsheets = 'all';
    this.allOt = null;
    this.fetchingAll = true;
    this.allPage = "1";
    this.allPending = null;
    this.fetchingPending = true;
    this.pendingPage = "1";
    this.allOvertime();
  }

  applyOtModal() {
    this.router.navigate(['/members/apply-ot']);
  }

  doRefreshApproved(event: any) {
    this.fetchingApprove = true;
    this.approvePage = "1";

    setTimeout(() => {
      this.hoursProv.getApprovedOvertime(this.approvePage).then(res=>{
        let rslt: any = res;
        this.allApprove = rslt.list;
        this.approveTotal = parseInt(rslt.total, 10);
        this.approvePage = parseInt(rslt.page, 10);
        this.fetchingApprove = false;
        event.target.complete();
    }).catch(err=>{
        console.log(err);
        this.fetchingApprove = false;
        event.target.complete();
    });
      
    }, 2000);
  }

  doRefreshPending(event: any) {
    this.fetchingPending = true;
    this.pendingPage = "1";

    setTimeout(() => {
      this.hoursProv.getPendingOvertime(this.pendingPage).then(res=>{
        let rslt: any = res;
        this.allPending = rslt.list;
        this.pendingTotal = parseInt(rslt.total, 10);
        this.pendingPage = parseInt(rslt.page, 10);
        this.fetchingPending = false;
        event.target.complete();
    }).catch(err=>{
        console.log(err);
        this.fetchingPending = false;
        event.target.complete();
    });
      
    }, 2000);
  }

  doRefreshReject(event: any) {
    this.fetchingReject = true;
    this.rejectPage = "1";

    setTimeout(() => {
      this.hoursProv.getRejectedOvertime(this.rejectPage).then(res=>{
        let rslt: any = res;
        this.allReject = rslt.list;
        this.rejectTotal = parseInt(rslt.total, 10);
        this.rejectPage = parseInt(rslt.page, 10);
        this.fetchingReject = false;
        event.target.complete();
    }).catch(err=>{
        console.log(err);
        this.fetchingReject = false;
        event.target.complete();
    });
      
    }, 2000);
  }

  approveClick() {
    if (!this.allApprove) {
        this.approvedOvertime();
    }        
  }

  pendingClick() {
      if (!this.allPending) {
          this.pendingOvertime();
      }        
  }

  rejectClick() {
      if (!this.allReject) {
          this.rejectedOvertime();
      }
  }

  approvedOvertime() {
    this.hoursProv.getApprovedOvertime(this.approvePage).then(res=>{
        let rslt: any = res;
        this.allApprove = rslt.list;
        this.approveTotal = parseInt(rslt.total, 10);
        this.approvePage = parseInt(rslt.page, 10);
        this.fetchingApprove = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingApprove = false;
    });
  }

  pendingOvertime() {
    this.hoursProv.getPendingOvertime(this.pendingPage).then(res=>{
      let rslt: any = res;
      this.allPending = rslt.list;
      this.pendingTotal = parseInt(rslt.total, 10);
      this.pendingPage = parseInt(rslt.page, 10);
      this.fetchingPending = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingPending = false;
    });
  }

  rejectedOvertime() {
    this.hoursProv.getRejectedOvertime(this.rejectPage).then(res=>{
      let rslt: any = res;
      this.allReject = rslt.list;
      this.rejectTotal = parseInt(rslt.total, 10);
      this.rejectPage = parseInt(rslt.page, 10);
      this.fetchingReject = false;
    }).catch(err=>{
        console.log(err);
        this.fetchingReject = false;
    });
  }

  gotoDetails(data: any) {
    let navigationExtras: NavigationExtras = {
      state: {
        details: data
      }
    };
    this.router.navigate(['/members/overtime-details'], navigationExtras);
  }

  allOvertime() {
    
    this.hoursProv.getAllOvertime(this.allPage).then(res=>{
        let rslt: any = res;
        this.allOt = rslt.list;
        this.allTotal = parseInt(rslt.total, 10);
        this.allPage = parseInt(rslt.page, 10);
        this.fetchingAll = false;
        
    }).catch(err=>{
        
        console.log(err);
        this.fetchingAll = false;
    });
  }

  doInfiniteAll(event: any) {
    this.allPage = this.allPage+1;
                
    setTimeout(() => {
        this.hoursProv.getAllOvertime(this.allPage+'').then(res=>{
            let rslt: any = res;
            if (rslt.list) {
              for(let i=0; i<rslt.list.length; i++) {
                  this.allOt.push(rslt.list[i]);
              }
            }
            event.target.complete();
        }).catch(err=>{
          event.target.complete();
            console.log(err);
        });

        if (this.allPage == this.allTotal) {
          event.target.complete();
        }
    }, 500);
  }

  doInfinitePending(event: any) {
    this.pendingPage = this.pendingPage+1;
                
    setTimeout(() => {
        this.hoursProv.getPendingOvertime(this.pendingPage+'').then(res=>{
            let rslt: any = res;
            if (rslt.list) {
              for(let i=0; i<rslt.list.length; i++) {
                  this.allPending.push(rslt.list[i]);
              }
            }
            event.target.complete();
        }).catch(err=>{
          event.target.complete();
            console.log(err);
        });

        if (this.pendingPage == this.pendingTotal) {
          event.target.complete();
        }
    }, 500);
  }

  doInfiniteReject(event: any) {
    this.rejectPage = this.rejectPage+1;
                
    setTimeout(() => {
        this.hoursProv.getRejectedOvertime(this.rejectPage+'').then(res=>{
            let rslt: any = res;
            if (rslt.list) {
              for(let i=0; i<rslt.list.length; i++) {
                this.allReject.push(rslt.list[i]);
              }
            }
            event.target.complete();
        }).catch(err=>{
          event.target.complete();
            console.log(err);
        });

        if (this.rejectPage == this.rejectTotal) {
          event.target.complete();
        }
    }, 500);
  }

  doInfiniteApprove(event: any) {
    this.approvePage = this.approvePage+1;
                
    setTimeout(() => {
        this.hoursProv.getApprovedOvertime(this.approvePage+'').then(res=>{
            let rslt: any = res;
            if (rslt.list) {
              for(let i=0; i<rslt.list.length; i++) {
                  this.allApprove.push(rslt.list[i]);
              }
            }
            event.target.complete();
        }).catch(err=>{
            event.target.complete();
            console.log(err);
        });

        if (this.approvePage == this.approveTotal) {
          event.target.complete();
        }
    }, 500);
  }

  doRefreshAll(event: any) {
    this.allPage = "1";

    

    setTimeout(() => {

      this.hoursProv.getAllOvertime(this.allPage).then(res=>{
          let rslt: any = res;
          this.allOt = rslt.list;
          this.allTotal = parseInt(rslt.total, 10);
          this.allPage = parseInt(rslt.page, 10);
          this.fetchingAll = false;          

          event.target.complete();
      }).catch(err=>{          
          console.log(err);
          this.fetchingAll = false;
          event.target.complete();
      });
      
    }, 2000);
  }

}
