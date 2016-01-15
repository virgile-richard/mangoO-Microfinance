<!DOCTYPE HTML>
<?PHP
	include 'functions.php';
	check_logon();
	check_report();
	connect();
	
	//Variables $year and $month provide the pre-set values for input fields
	$year = date("Y",time()); 
	$month = date("m",time());
?>
<html>
	<?PHP htmlHead('Capital Report',1) ?>	
	<body>
			
		<!-- MENU -->
		<?PHP 
				menu_Tabs(5);
		?>
		
		<!-- MENU MAIN -->
		<div id="menu_main">
			<a href="rep_incomes.php">Incomes Report</a>
			<a href="rep_expenditures.php">Expenditures Report</a>
			<a href="rep_loans.php">Loans Report</a>
			<a href="rep_capital.php" id="item_selected">Capital Report</a>
			<a href="rep_monthly.php">Monthly Report</a>
			<a href="rep_annual.php">Annual Report</a>
		</div>
			
		<!-- MENU: Selection Bar -->
		<div id="menu_selection">
			<form action="rep_capital.php" method="post">
				<input type="number" min="2006" max="2206" name="rep_year" style="width:100px;" value="<?PHP if ($month == 01) echo $year-1; else echo $year; ?>" placeholder="Give Year" />
				<select name="rep_month" style="height:24px">
					<option value="01" <?PHP if ($month == 02) echo 'selected="selected"' ?> >January</option>
					<option value="02" <?PHP if ($month == 03) echo 'selected="selected"' ?> >February</option>
					<option value="03" <?PHP if ($month == 04) echo 'selected="selected"' ?> >March</option>
					<option value="04" <?PHP if ($month == 05) echo 'selected="selected"' ?> >April</option>
					<option value="05" <?PHP if ($month == 06) echo 'selected="selected"' ?> >May</option>
					<option value="06" <?PHP if ($month == 07) echo 'selected="selected"' ?> >June</option>
					<option value="07" <?PHP if ($month == 08) echo 'selected="selected"' ?> >July</option>
					<option value="08" <?PHP if ($month == 09) echo 'selected="selected"' ?> >August</option>
					<option value="09" <?PHP if ($month == 10) echo 'selected="selected"' ?> >September</option>
					<option value="10" <?PHP if ($month == 11) echo 'selected="selected"' ?> >October</option>
					<option value="11" <?PHP if ($month == 12) echo 'selected="selected"' ?> >November</option>
					<option value="12" <?PHP if ($month == 01) echo 'selected="selected"' ?> >December</option>
				</select>
				<input type="submit" name="select" value="Select Report" />
			</form>
		</div>
		
		<?PHP
		if(isset($_POST['select'])){
			
			//Sanitize user input
			$rep_month = sanitize($_POST['rep_month']);
			$rep_year = sanitize($_POST['rep_year']);
			
			//Calculate UNIX TIMESTAMP for first and last day of selected month
			$firstDay = mktime(0, 0, 0, $rep_month, 1, $rep_year);
			$lastDay = mktime(0, 0, 0, ($rep_month+1), 0, $rep_year);
			
			//Make array for exporting data
			$_SESSION['rep_export'] = array();
			$_SESSION['rep_exp_title'] = $rep_year.'-'.$rep_month.'_capital';
			
			//Select newly bought Shares from SHARES
			$sql_shares = "SELECT * FROM shares WHERE share_date BETWEEN $firstDay AND $lastDay";
			$query_shares = mysql_query($sql_shares);
			check_sql ($query_shares);
			$total_shares = 0;
			while($row_shares = mysql_fetch_assoc($query_shares)){
				$total_shares = $total_shares + ($row_shares['share_amount']*$row_shares['share_value']);
			}
			
			//Select Saving Deposits from SAVINGS
			$sql_savdep = "SELECT * FROM savings WHERE sav_date BETWEEN $firstDay AND $lastDay AND savtype_id = 1";
			$query_savdep = mysql_query($sql_savdep);
			check_sql ($query_savdep);
			$total_savdep = 0;
			while($row_savdep = mysql_fetch_assoc($query_savdep)){
				$total_savdep = $total_savdep + $row_savdep['sav_amount'];
			}
			
			//Select Loan Recoveries from LOANS
			$sql_loanrec = "SELECT * FROM ltrans WHERE ltrans_date BETWEEN $firstDay AND $lastDay";
			$query_loanrec = mysql_query($sql_loanrec);
			check_sql ($query_loanrec);
			$total_loanrec = 0;
			while($row_loanrec = mysql_fetch_assoc($query_loanrec)){
				$total_loanrec = $total_loanrec + $row_loanrec['ltrans_principal'];
			}
			
			//Select Saving Withdrawals from SAVINGS
			$sql_savwithd = "SELECT * FROM savings WHERE sav_date BETWEEN $firstDay AND $lastDay AND savtype_id = 2";
			$query_savwithd = mysql_query($sql_savwithd);
			check_sql ($query_savwithd);
			$total_savwithd = 0;
			while($row_savwithd = mysql_fetch_assoc($query_savwithd)){
				$total_savwithd = $total_savwithd + $row_savwithd['sav_amount'];
			}
			$total_savwithd = $total_savwithd * (-1);
			
			//Select Loans Out from LOANS
			$sql_loanout = "SELECT * FROM loans WHERE loan_dateout BETWEEN $firstDay AND $lastDay";
			$query_loanout = mysql_query($sql_loanout);
			check_sql ($query_loanout);
			$total_loanout = 0;
			while($row_loanout = mysql_fetch_assoc($query_loanout)){
				$total_loanout = $total_loanout + $row_loanout['loan_principal'];
			}
			
			//Prepare data for export to Excel file
			array_push($_SESSION['rep_export'], array("Type" => "Shares", "Amount" => $total_shares));
			array_push($_SESSION['rep_export'], array("Type" => "Saving Deposits", "Amount" => $total_savdep));
			array_push($_SESSION['rep_export'], array("Type" => "Loan Recoveries", "Amount" => $total_loanrec));
			
			array_push($_SESSION['rep_export'], array("Type" => "Total Additions", "Amount" => $total_loanrec+$total_savdep+$total_shares));
			array_push($_SESSION['rep_export'], array("Type" => "", "Amount" => ""));
			
			array_push($_SESSION['rep_export'], array("Type" => "Saving Withdrawals", "Amount" => $total_savwithd));
			array_push($_SESSION['rep_export'], array("Type" => "Loans Out", "Amount" => $total_loanout));
			
			array_push($_SESSION['rep_export'], array("Type" => "Total Deductions", "Amount" => $total_loanout+$total_savwithd));
			?>
									
			<!-- Export Button -->					
			<form class="export" action="rep_export.php" method="post">
				<input type="submit" name="export_rep" value="Export Report" />
			</form>
			
			<!-- TABLE 1: Capital Additions -->
			<table id="tb_table" style="width:50%">
				<colgroup>
					<col width="50%"/>
					<col width="50%"/>
				</colgroup>
				<tr>
					<th class="title" colspan="2">Capital Additions for <?PHP echo $rep_month.'/'.$rep_year; ?></th>
				</tr>
				<tr>
					<th>Type</th>
					<th>Amount</th>
				</tr>
				<tr>
					<td>Shares</td>
					<td><?PHP echo number_format($total_shares).' '.$_SESSION['set_cur'] ?></td>
				</tr>
				<tr class="alt">
					<td>Saving Deposits</td>
					<td><?PHP echo number_format($total_savdep).' '.$_SESSION['set_cur'] ?></td>
				</tr>
				<tr>
					<td>Loan Recoveries</td>
					<td><?PHP echo number_format($total_loanrec).' '.$_SESSION['set_cur'] ?></td>
				</tr>
				<tr class="balance">
					<td>Total Capital Additions:</td>
					<td><?PHP echo number_format($total_shares + $total_savdep + $total_loanrec).' '.$_SESSION['set_cur'] ?></td>
				</tr>
			</table>
			
			<!-- TABLE 2: Capital Deductions -->
			<table id="tb_table" style="width:50%">
				<colgroup>
					<col width="50%"/>
					<col width="50%"/>
				</colgroup>
				<tr>
					<th class="title" colspan="2">Capital Deductions for <?PHP echo $rep_month.'/'.$rep_year; ?></th>
				</tr>
				<tr>
					<th>Type</th>
					<th>Amount</th>
				</tr>
				<tr>
					<td>Loans Out</td>
					<td><?PHP echo number_format($total_loanout).' '.$_SESSION['set_cur'] ?></td>
				</tr>
				<tr class="alt">
					<td>Saving Withdrawals</td>
					<td><?PHP echo number_format($total_savwithd).' '.$_SESSION['set_cur'] ?></td>
				</tr>
				<tr class="balance">
					<td>Total Capital Deductions:</td>
					<td><?PHP echo number_format($total_loanout+$total_savwithd).' '.$_SESSION['set_cur'] ?></td>
				</tr>
			</table>
		<?PHP
		}
		?>
	</body>
</html>