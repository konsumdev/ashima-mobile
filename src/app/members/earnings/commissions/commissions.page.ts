import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { CommissionProvider } from 'src/app/services/commission/commission';

@Component({
  selector: 'app-commissions',
  templateUrl: './commissions.page.html',
  styleUrls: ['./commissions.page.scss'],
})
export class CommissionsPage implements OnInit {
  commissions: any;
  fakeUsers: Array<any>;
  fetchingAll: any;

  constructor(
    private mainApp : AppComponent,
    private comProv: CommissionProvider,
  ) {
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
  }

  ngOnInit() {
    this.getEmployeeCommissions();
  }

  getEmployeeCommissions() {
    this.comProv.getCommissions().then( res => {
      this.fetchingAll = false;
        let rslt: any = res;

        if (rslt) {
            this.commissions = rslt;
        }
    }).catch( error => {
        this.fetchingAll = false;
        console.log(error);
    });
  }

}
