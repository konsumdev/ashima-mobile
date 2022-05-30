import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-compensation-history',
  templateUrl: './compensation-history.page.html',
  styleUrls: ['./compensation-history.page.scss'],
})
export class CompensationHistoryPage implements OnInit {
  historyDetails: any;

  constructor(
    private route: ActivatedRoute, 
    private router: Router,
  ) { 
    this.route.queryParams.subscribe(params => {
      if (this.router.getCurrentNavigation().extras.state) {
        this.historyDetails = this.router.getCurrentNavigation().extras.state.comHstryDet;
      }
    });
  }

  ngOnInit() {
  }

}
